<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** The member list: at least five, one representative, names in parts, and a spreadsheet route in. */
class MemberListTest extends TestCase
{
    use RefreshDatabase;

    private function rep(): User
    {
        $rep = User::factory()->create(['name' => 'Ana Reyes']);
        Organization::factory()->for($rep)->create();

        return $rep;
    }

    private function save(User $rep, array $members)
    {
        $organization = $rep->organization;

        return $this->actingAs($rep)->from(route('cso.profile.edit'))->patch(route('cso.profile.update'), [
            'name' => $organization->name, 'sector' => $organization->sector, 'barangay' => $organization->barangay,
            'members' => $members,
        ]);
    }

    public function test_fewer_than_five_members_or_no_representative_is_refused(): void
    {
        $rep = $this->rep();

        $this->save($rep, $this->memberRows(4))->assertSessionHasErrors('members');

        $noRep = $this->memberRows();
        $noRep[0]['position'] = 'President';
        $this->save($rep, $noRep)->assertSessionHasErrors(['members' => 'Mark one member as the Representative.']);

        $twoReps = $this->memberRows();
        $twoReps[1]['position'] = 'representative';
        $this->save($rep, $twoReps)->assertSessionHasErrors(['members' => 'Only one member can be the Representative.']);

        $missingName = $this->memberRows();
        $missingName[3]['first_name'] = '';
        $this->save($rep, $missingName)->assertSessionHasErrors('members.3.first_name');

        $this->save($rep, $this->memberRows(7))->assertSessionHasNoErrors();
        $this->assertCount(7, $rep->organization->members);
    }

    public function test_the_page_lists_the_representative_first_from_the_account(): void
    {
        $rep = $this->rep();

        $this->actingAs($rep)->get(route('cso.profile.edit'))->assertOk()
            ->assertSee('x-data="memberTable(', false)
            ->assertSee('Representative')->assertSee('Reyes')
            ->assertSee('of at least 5 members');
    }

    public function test_the_template_downloads_and_an_upload_is_read_for_review(): void
    {
        $rep = $this->rep();

        $this->actingAs($rep)->get(route('cso.members.template'))->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=member-list-template.xlsx');

        $csv = "Last name,First name,Middle name,Position,Contact number,Email\n"
            ."Cruz,Ben,,Treasurer,0917 000 0000,ben@example.ph\n"
            .",Nameless,,,,\n"
            ."\n"
            ."Santos,Carla,Diaz,,,\n";
        $file = UploadedFile::fake()->createWithContent('members.csv', $csv);

        $this->actingAs($rep)->postJson(route('cso.members.import'), ['file' => $file])->assertOk()
            ->assertJsonCount(2, 'rows')
            ->assertJsonPath('rows.0.last_name', 'Cruz')
            ->assertJsonPath('rows.1.middle_name', 'Diaz')
            ->assertJsonPath('skipped.0', 'Row 3: the last name and first name are needed.');

        // Nothing was saved: the person reviews the rows first.
        $this->assertCount(0, $rep->organization->members);

        $empty = UploadedFile::fake()->createWithContent('empty.csv', "Last name,First name\n");
        $this->actingAs($rep)->postJson(route('cso.members.import'), ['file' => $empty])->assertStatus(422);
    }
}
