<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrganizationProfileController extends Controller
{
    public function edit(): View
    {
        return view('cso.profile', [
            'organization' => auth()->user()->organization,
            'sectors' => config('sectors'),
            'barangays' => config('barangays'),
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $organization = $request->user()->organization;
        $data = $request->safe()->only(['name', 'sector', 'barangay', 'advocacy', 'org_chart']);

        if ($request->hasFile('logo')) {
            // Logos are shown publicly, so they sit on the public disk. Accreditation
            // documents never do (see DocumentController).
            if ($organization?->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        DB::transaction(function () use ($request, $organization, $data) {
            $organization = $organization
                ? tap($organization)->update($data)
                : $request->user()->organization()->create($data);

            $members = collect($request->validated('members', []))
                ->filter(fn (array $member) => filled($member['name'] ?? null));

            $organization->members()->delete();
            $organization->members()->createMany($members->all());
        });

        return redirect()
            ->route('cso.profile.edit')
            ->with('status', 'Organization profile saved.');
    }
}
