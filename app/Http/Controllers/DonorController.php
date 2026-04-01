<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonorController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['amount', 'recent', 'count']) ? $request->query('sort') : 'amount';

        $identityExpr = "COALESCE(NULLIF(TRIM(donor_email), ''), NULLIF(TRIM(donor_phone), ''), NULLIF(TRIM(donor_name), ''))";
        $identity = DB::raw($identityExpr);

        $donors = Donation::query()
            ->select([
                DB::raw("MAX(donor_name) as donor_name"),
                DB::raw("MAX(donor_email) as donor_email"),
                DB::raw("MAX(donor_phone) as donor_phone"),
                DB::raw("SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as total_amount"),
                DB::raw("SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as donation_count"),
                DB::raw("MAX(paid_at) as last_paid_at"),
                DB::raw($identityExpr . " as identity"),
            ])
            ->where(function ($w) {
                $w->whereNotNull('donor_email')
                  ->orWhereNotNull('donor_phone')
                  ->orWhereNotNull('donor_name');
            })
            ->groupBy(DB::raw($identityExpr));

        if ($q !== '') {
            $donors->where(function ($w) use ($q) {
                $w->where('donor_name', 'like', "%$q%")
                  ->orWhere('donor_email', 'like', "%$q%")
                  ->orWhere('donor_phone', 'like', "%$q%");
            });
        }

        if ($sort === 'recent') {
            $donors->orderByDesc('last_paid_at');
        } elseif ($sort === 'count') {
            $donors->orderByDesc('donation_count');
        } else { // amount
            $donors->orderByDesc('total_amount');
        }

        $paginated = $donors->paginate(20)->withQueryString();
        $org = Organization::query()->first();

        return view('donor.index', [
            'donors' => $paginated,
            'q' => $q,
            'sort' => $sort,
            'org' => $org,
        ]);
    }
}
