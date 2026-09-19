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
            'stats' => [
                ['value' => Accreditation::where('status', 'active')->count(), 'label' => 'Accredited organizations'],
                ['value' => Activity::where('status', 'verified')->count(), 'label' => 'Verified activities'],
                ['value' => number_format(Activity::where('status', 'verified')->sum('participants_estimate')), 'label' => 'Residents reached'],
                ['value' => Organization::whereHas('accreditations', fn ($q) => $q->where('status', 'active'))
                    ->distinct('barangay')->count('barangay'), 'label' => 'Barangays represented'],
            ],
            'sectors' => $this->accreditedQuery()
                ->selectRaw('sector as name, count(*) as count')
                ->groupBy('sector')
                ->orderByDesc('count')
                ->limit(8)
                ->get()
                ->toArray(),
            'featured' => $this->accreditedQuery()
                ->latest('id')
                ->limit(3)
                ->get()
                ->map(fn ($org) => [
                    'name' => $org->name,
                    'sector' => $org->sector,
                    'barangay' => $org->barangay,
                    'advocacy' => $org->advocacy,
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
                ->map(fn ($label, $key) => [
                    'label' => $label,
                    'note' => config("office.requirement_notes.$key", 'Required for both new and renewal applications.'),
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

        return view('public.organization', [
            'organization' => $organization,
            'accreditation' => $accreditation,
            'activities' => $organization->activities()
                ->where('status', 'verified')
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
    private function accreditedQuery()
    {
        return Organization::query()
            ->where('public_visibility', true)
            ->whereHas('accreditations', fn ($q) => $q->where('status', 'active'));
    }
}
