<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route as RouteFacade;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Static/top-level pages
        $urls[] = [
            'loc' => route('home'),
            'changefreq' => 'daily',
            'priority' => '1.0',
            'lastmod' => now()->toAtomString(),
        ];
        if (RouteFacade::has('program.index')) {
            $urls[] = [
                'loc' => route('program.index'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => now()->toAtomString(),
            ];
        }
        if (RouteFacade::has('news.index')) {
            $urls[] = [
                'loc' => route('news.index'),
                'changefreq' => 'daily',
                'priority' => '0.7',
                'lastmod' => now()->toAtomString(),
            ];
        }
        if (RouteFacade::has('donor.index')) {
            $urls[] = [
                'loc' => route('donor.index'),
                'changefreq' => 'weekly',
                'priority' => '0.4',
                'lastmod' => now()->toAtomString(),
            ];
        }

        // Categories (as homepage filters)
        Category::query()
            ->orderBy('name')
            ->get(['slug','updated_at'])
            ->each(function ($cat) use (&$urls) {
                $urls[] = [
                    'loc' => route('home', ['category' => $cat->slug]),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => optional($cat->updated_at)->toAtomString() ?? now()->toAtomString(),
                ];
            });

        // Campaigns (active only)
        Campaign::query()
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->get(['slug','updated_at'])
            ->each(function ($c) use (&$urls) {
                $urls[] = [
                    'loc' => route('campaign.show', $c->slug),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                    'lastmod' => optional($c->updated_at)->toAtomString(),
                ];
            });

        // News (published)
        News::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->get(['slug','published_at','updated_at'])
            ->each(function ($n) use (&$urls) {
                $lastmod = $n->updated_at && $n->updated_at->gt($n->published_at ?? $n->updated_at)
                    ? $n->updated_at
                    : ($n->published_at ?: $n->updated_at);
                $urls[] = [
                    'loc' => route('news.show', $n->slug),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => optional($lastmod)->toAtomString(),
                ];
            });

        // Pages (published)
        Page::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->get(['slug','published_at','updated_at'])
            ->each(function ($p) use (&$urls) {
                $lastmod = $p->updated_at && $p->updated_at->gt($p->published_at ?? $p->updated_at)
                    ? $p->updated_at
                    : ($p->published_at ?: $p->updated_at);
                $urls[] = [
                    'loc' => route('page.show', $p->slug),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                    'lastmod' => optional($lastmod)->toAtomString(),
                ];
            });

        // Build XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</lastmod>\n";
            }
            if (!empty($u['changefreq'])) {
                $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            }
            if (!empty($u['priority'])) {
                $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Sitemap: ' . route('sitemap'),
        ];
        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
