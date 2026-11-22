<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Rules\PersonName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * A person's name takes letters, spaces, hyphens, apostrophes and periods, not numbers or other
 * symbols: on accounts, the member list (typed or imported), and the application form's names.
 */
class PersonNameTest extends TestCase
{
    use RefreshDatabase;

    private function rep(): User
    {
        $rep = User::factory()->create();
        Organization::factory()->for($rep)->create();

        return $rep;
    }

    public function test_real_names_pass_and_numbers_or_symbols_do_not(): void
    {
        foreach (['Dela Cruz-Santos', 'Ma. Cristina', "O'Neil", 'Peñaflor', 'José', 'De los Reyes'] as $name) {
            $this->assertTrue(PersonName::valid($name), $name);
        }
        foreach (['Juan2', '123', 'J@ne', 'Ana_Marie', '-Ana', 'Ana!'] as $name) {
            $this->assertFalse(PersonName::valid($name), $name);
        }
    }

    public function test_account_names_refuse_numbers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', ['last_name' => 'Reyes2', 'first_name' => 'Ana', 'middle_name' => '#', 'email' => $user->email])
            ->assertSessionHasErrors(['last_name' => PersonName::MESSAGE, 'middle_name' => PersonName::MESSAGE]);
        $this->actingAs($user)->patch('/profile', ['last_name' => 'Dela Cruz-Santos', 'first_name' => 'Ma. Cristina', 'email' => $user->email, 'phone' => '0917 765 4321'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Ma. Cristina Dela Cruz-Santos', $user->fresh()->name);

        // The inputs carry the same rule, so the browser catches it as the name is typed.
        $this->actingAs($user)->get('/profile')->assertSee('data-person-name', false)->assertSee('pattern="', false);
    }

    public function test_the_member_list_refuses_numbers_in_names(): void
    {
        $rep = $this->rep();
        $rows = $this->memberRows();
        $rows[2]['first_name'] = 'Pedro3';

        $this->actingAs($rep)->get(route('cso.application'));
        $this->actingAs($rep)->post(route('cso.application.members'), ['step' => 'members', 'action' => 'next', 'members' => $rows])
            ->assertSessionHasErrors(['members.2.first_name' => PersonName::MESSAGE]);
        $this->assertSame(0, $rep->organization->members()->count());
    }

    public function test_an_imported_row_with_numbers_in_the_name_is_skipped_with_the_reason(): void
    {
        $csv = "Last name,First name,Middle name,Position,Contact number,Email\n"
            ."Cruz,Ben,,Treasurer,,\n"
            ."Santos,Carla2,,,,\n";

        $this->actingAs($this->rep())->postJson(route('cso.members.import'), ['file' => UploadedFile::fake()->createWithContent('members.csv', $csv)])
            ->assertOk()->assertJsonCount(1, 'rows')
            ->assertJsonPath('skipped.0', 'Row 3: the name has numbers or symbols (Carla2 Santos).');
    }

    public function test_the_application_forms_names_refuse_numbers(): void
    {
        $rep = $this->rep();
        $this->startApplication($rep);

        $this->actingAs($rep)->post(route('cso.application.form'), [
            'step' => 'form', 'action' => 'next', 'type' => 'new',
            'organization_name' => 'Purok 3 Women\'s Association',   // an organization name may have numbers
            'representative_name' => 'Ana R3yes', 'contact_number' => '0917 123 4567', 'place_barangay' => 'Poblacion',
            'place_detail' => '', 'certify' => '1', 'certified_name' => 'Ana 4',
        ])->assertSessionHasErrors(['representative_name', 'certified_name'])->assertSessionDoesntHaveErrors('organization_name');
    }
}
