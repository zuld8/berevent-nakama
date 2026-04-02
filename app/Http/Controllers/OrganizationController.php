<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Event;
use App\Models\Campaign;

class OrganizationController extends Controller
{
    public function show(string $slug)
    {
        $org = Organization::where('slug', $slug)->firstOrFail();

        $events = Event::where('organization_id', $org->id)
            ->where('status', 'published')
            ->orderByDesc('start_date')
            ->get();

        $campaigns = Campaign::with('organization')
            ->where('organization_id', $org->id)
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->get();

        return view('organization.show', compact('org', 'events', 'campaigns'));
    }
}
