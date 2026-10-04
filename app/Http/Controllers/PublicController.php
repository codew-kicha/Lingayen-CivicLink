<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Activity;
use App\Models\AnnualReport;
use App\Models\NewsPost;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            // Newest verified activity per organization, and only organizations the public
            // directory already shows.
            'ticker' => Activity::query()
                ->where('status', 'verified')
                ->whereIn('organization_id', $this->accreditedQuery()->select('id'))
                ->with('organization:id,name,barangay')
                ->latest('verified_at')
                ->limit(40)
                ->get()
                ->unique('organization_id')
                ->take(6)
                ->values()
                ->map(fn (Activity $activity) => [
                    'title' => $activity->title,
                    'organization' => $activity->organization->name,
                    'barangay' => $activity->organization->barangay,
                    'held' => $this->heldAgo($activity->activity_date),
                    'url' => route('directory.show', $activity->organization_id),
                ])
                ->all(),
            'stats' => [
                ['value' => Accreditation::where('status', 'active')->count(), 'label' => 'Accredited organizations'],
                ['value' => Activity::where('status', 'verified')->count(), 'label' => 'Verified activities'],
                ['value' => (int) Activity::where('status', 'verified')->sum('participants_estimate'), 'label' => 'Residents reached'],
                ['value' => Organization::whereHas('accreditations', fn ($q) => $q->where('status', 'active'))
                    ->distinct('barangay')->count('barangay'), 'label' => 'Barangays represented'],
            ],
            'sectors' => $this->accreditedQuery()
                ->selectRaw('sector as name, count(*) as count')
                ->groupBy('sector')
                ->orderByDesc('count')
                ->orderBy('sector')
                ->get()
                ->toArray(),
            'featured' => Accreditation::query()
                ->where('status', 'active')
                ->whereIn('organization_id', $this->accreditedQuery()->select('id'))
                ->with('organization')
                ->latest('issued_at')
                ->latest('id')
                ->limit(4)
                ->get()
                ->map(fn (Accreditation $accreditation) => [
                    'url' => route('directory.show', $accreditation->organization),
                    'name' => $accreditation->organization->name,
                    'sector' => $accreditation->organization->sector,
                    'barangay' => $accreditation->organization->barangay,
                    'advocacy' => $accreditation->organization->advocacy,
                    'accredited_on' => $accreditation->issued_at,
                ])
                ->all(),
            'news' => NewsPost::where('status', 'published')
                ->latest('published_at')
                ->limit(3)
                ->get()
                ->map(fn ($post) => [
                    'date' => $post->published_at?->format('d M Y'),
                    'title' => $post->title,
                    'excerpt' => Str::limit(strip_tags($post->body), 140),
                ])
                ->all(),
        ]);
    }

    public function about(): View
    {
        return view('public.about', [
            'officials' => config('office.officials'),
        ]);
    }

    public function accreditation(): View
    {
        return view('public.accreditation', [
            'process' => config('office.process'),
            'requirements' => collect(config('document_types'))
                ->map(fn (array $type, string $key) => [
                    'label' => $type['label'],
                    'optional' => $type['optional'],
                    'note' => config("office.requirement_notes.$key"),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function directory(Request $request): View
    {
        $organizations = $this->accreditedQuery()
            ->when($request->string('q')->trim()->value(), fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when($request->input('sector'), fn ($query, $sector) => $query->where('sector', $sector))
            ->when($request->input('barangay'), fn ($query, $barangay) => $query->where('barangay', $barangay))
            ->orderBy('barangay')
            ->orderBy('sector')
            ->orderBy('name')
            ->paginate(12);

        return view('public.directory', [
            'organizations' => $organizations,
            'sectors' => config('sectors'),
            'barangays' => config('barangays'),
        ]);
    }

    public function organization(Organization $organization): View
    {
        abort_unless($organization->public_visibility, 404);

        $accreditation = $organization->accreditations()->where('status', 'active')->first();

        abort_unless($accreditation !== null, 404);

        $verified = $organization->activities()->where('status', 'verified');

        return view('public.organization', [
            'organization' => $organization,
            'accreditation' => $accreditation,
            'ledger' => [
                'count' => (clone $verified)->count(),
                'reached' => (int) (clone $verified)->sum('participants_estimate'),
                'since' => (clone $verified)->min('activity_date'),
            ],
            'activities' => (clone $verified)
                ->latest('activity_date')
                ->limit(20)
                ->get(),
        ]);
    }

    public function resources(): View
    {
        return view('public.resources', [
            'posts' => NewsPost::with('organizations')
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(10),
            'reports' => AnnualReport::orderByDesc('year')->get(),
            'faqs' => config('office.faqs'),
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    /**
     * Publicly listable organizations: visible in the directory and currently accredited.
     */
    // activity_date has no time component, so "8 hours ago" would be misleading.
    private function heldAgo(\Illuminate\Support\Carbon $date): string
    {
        return match (true) {
            $date->isToday() => 'today',
            $date->isYesterday() => 'yesterday',
            default => $date->diffForHumans(now()->startOfDay(), ['syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]),
        };
    }

    private function accreditedQuery()
    {
        return Organization::query()
            ->where('public_visibility', true)
            ->whereHas('accreditations', fn ($q) => $q->where('status', 'active'));
    }
}
