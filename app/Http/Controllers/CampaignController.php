<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignArticle;
use App\Models\Donation;
use App\Services\Payments\PaymentMethodCatalog;
use Illuminate\Validation\Rule;
use App\Services\WaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function donateForm(Request $request, string $slug)
    {
        $campaign = Campaign::query()
            ->with(['organization:id,name,meta_json'])
            ->where('slug', $slug)
            ->firstOrFail();

        $waCfg = (new WaService())->getConfig();
        $waValidationEnabled = (bool)($waCfg['validate_enabled'] ?? false) && !empty($waCfg['validate_client_id']);

        $meta = $campaign->organization?->meta_json ?? [];
        $automaticEnabled = (bool) data_get($meta, 'payments.enabled.automatic', true);
        $manualEnabled = (bool) data_get($meta, 'payments.enabled.manual', true);

        // Load active Midtrans methods for inline selection on the form
        $midtransMethods = [];
        if ($automaticEnabled) {
            $midtransMethods = (new PaymentMethodCatalog())->activeMidtrans();
        }

        return view('donation.create', [
            'c' => $campaign,
            'waValidationEnabled' => $waValidationEnabled,
            'automaticEnabled' => $automaticEnabled,
            'manualEnabled' => $manualEnabled,
            'midtransMethods' => $midtransMethods,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $campaign = Campaign::query()
            ->with(['organization:id,name,meta_json', 'categories:id,name,slug', 'media' => fn ($q) => $q->orderBy('sort_order')])
            ->where('slug', $slug)
            ->firstOrFail();

        $tab = in_array($request->query('tab'), ['detail', 'laporan', 'donatur']) ? $request->query('tab') : 'detail';

        $articles = CampaignArticle::query()
            ->with(['payout'])
            ->where('campaign_id', $campaign->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(8)
            ->withQueryString();

        $donations = Donation::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->paginate(10)
            ->withQueryString();

        // Related campaigns: share at least one category, exclude current, limit 2
        $categoryIds = $campaign->categories->pluck('id');
        $related = collect();
        if ($categoryIds->isNotEmpty()) {
            $related = Campaign::query()
                ->with(['media' => fn ($q) => $q->orderBy('sort_order')])
                ->where('id', '!=', $campaign->id)
                ->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                })
                ->orderByDesc('updated_at')
                ->limit(2)
                ->get();
        }

        return view('campaign.show', [
            'c' => $campaign,
            'tab' => $tab,
            'articles' => $articles,
            'donations' => $donations,
            'related' => $related,
        ]);
    }

    public function donate(Request $request, string $slug)
    {
        $campaign = Campaign::query()->where('slug', $slug)->firstOrFail();

        // Prepare allowed midtrans method codes for validation
        $allowedMethodIds = (new PaymentMethodCatalog())->activeMidtrans();
        $allowedMethodIds = collect($allowedMethodIds)->pluck('id')->all();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_phone' => ['required', 'string', 'max:30'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'message' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'in:automatic,manual'],
            'payment_method' => ['nullable', 'string', 'required_if:payment_type,automatic', Rule::in($allowedMethodIds)],
        ]);

        // Optional WA number validation (server-side)
        $waCfg = (new WaService())->getConfig();
        if ((bool)($waCfg['validate_enabled'] ?? false) && !empty($waCfg['validate_client_id'])) {
            $svc = new WaService();
            $check = $svc->validateNumber($data['donor_phone']);
            if (! ($check['isRegistered'] ?? false)) {
                return back()->withErrors(['donor_phone' => 'Nomor WhatsApp tidak valid atau belum terdaftar.'])->withInput();
            }
        }

        $ref = 'DN-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(6));

        // Determine allowed payment types from org settings
        $meta = $campaign->organization?->meta_json ?? [];
        $automaticEnabled = (bool) data_get($meta, 'payments.enabled.automatic', true);
        $manualEnabled = (bool) data_get($meta, 'payments.enabled.manual', true);
        $requestedType = $data['payment_type'] ?? 'automatic';
        if ($requestedType === 'automatic' && ! $automaticEnabled && $manualEnabled) {
            $requestedType = 'manual';
        } elseif ($requestedType === 'manual' && ! $manualEnabled && $automaticEnabled) {
            $requestedType = 'automatic';
        }

        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'user_id' => auth()->id(),
            'donor_name' => $data['donor_name'] ?? null,
            'donor_email' => $data['donor_email'] ?? null,
            'donor_phone' => $data['donor_phone'] ?? null,
            'is_anonymous' => (bool)($data['is_anonymous'] ?? false),
            'amount' => $data['amount'],
            'currency' => 'IDR',
            'status' => 'initiated',
            'reference' => $ref,
            'message' => $data['message'] ?? null,
            'created_at' => now(),
        ]);

        // Persist selected payment type to meta_json for traceability
        $meta = $donation->meta_json ?? [];
        $meta['payment_type'] = $requestedType;
        if ($requestedType === 'automatic' && ! empty($data['payment_method'] ?? null)) {
            $meta['midtrans'] = ($meta['midtrans'] ?? []) + [
                'chosen_method' => (string) $data['payment_method'],
            ];
        }
        $donation->meta_json = $meta;
        $donation->save();

        // Optional: Send WA message after donation initiated (only once per donation)
        try {
            $svc = new WaService();
            $cfg = $svc->getConfig();
            if ((bool)($cfg['send_enabled'] ?? false) && ! empty($cfg['send_client_id'])) {
                // Skip if already sent before (by any event)
                $already = (bool) data_get($donation->meta_json, 'wa.sent');
                if ($already) {
                    // do nothing
                } else {
                $orgName = $campaign->organization?->name ?? config('app.name');
                $payUrl = route('donation.pay', ['reference' => $donation->reference]);
                if (($requestedType ?? null) === 'manual') {
                    $payUrl = route('donation.manual', ['reference' => $donation->reference]);
                }
                $vars = [
                    'donor_name' => (string)($donation->donor_name ?? ''),
                    'donor_phone' => (string)($donation->donor_phone ?? ''),
                    'donor_email' => (string)($donation->donor_email ?? ''),
                    'amount' => number_format((float)$donation->amount, 0, ',', '.'),
                    'amount_raw' => (string)$donation->amount,
                    'campaign_title' => (string)$campaign->title,
                    'campaign_url' => route('campaign.show', $campaign->slug),
                    'pay_url' => $payUrl,
                    'donation_reference' => (string)$donation->reference,
                    'organization_name' => (string)$orgName,
                ];
                // Use initiated/unpaid template
                $template = (string) ($cfg['message_template_initiated'] ?? ($cfg['message_template'] ?? ''));
                if ($template !== '' && ! empty($donation->donor_phone)) {
                    $message = $svc->renderTemplate($template, $vars);
                    $ok = $svc->sendText((string)$donation->donor_phone, $message);
                    if ($ok) {
                        $meta = $donation->meta_json ?? [];
                        $meta['wa'] = ($meta['wa'] ?? []) + [
                            'sent' => now()->toISOString(),
                            'sent_event' => 'initiated',
                        ];
                        $donation->meta_json = $meta;
                        $donation->save();
                    }
                }
                }
            }
        } catch (\Throwable $e) {
            // ignore WA failures silently
        }

        // Route based on selected payment type
        $paymentType = $requestedType;
        if ($paymentType === 'manual') {
            return redirect()->route('donation.manual', ['reference' => $donation->reference]);
        }
        // For automatic (Midtrans), we already have a chosen method on the form
        return redirect()->route('donation.pay', ['reference' => $donation->reference]);
    }
}
