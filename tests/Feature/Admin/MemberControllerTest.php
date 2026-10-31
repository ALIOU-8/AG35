<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use App\Models\MembershipFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_member_cannot_access_the_administration(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->get(route('admin.members.index'))
            ->assertForbidden();
    }

    public function test_suspended_bureau_member_loses_access_to_the_administration(): void
    {
        $this->actingAs(User::factory()->bureau()->suspended()->create())
            ->get(route('admin.members.index'))
            ->assertRedirect(route('account.pending'));
    }

    public function test_list_shows_pending_registrations_by_default(): void
    {
        User::factory()->create(['name' => 'Nouvelle Inscrite']);
        User::factory()->active()->create(['name' => 'Membre Actif']);

        $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('Nouvelle Inscrite')
            ->assertDontSee('Membre Actif');
    }

    public function test_approving_a_member_activates_them_and_adds_them_to_the_general_chat(): void
    {
        $pending = User::factory()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->post(route('admin.members.approve', $pending))
            ->assertRedirect()
            ->assertSessionHas('status');

        $pending->refresh();
        $this->assertTrue($pending->isActive());
        $this->assertNotNull($pending->approved_at);
        $this->assertTrue(Conversation::general()->hasParticipant($pending));
    }

    public function test_bureau_updates_member_details_but_cannot_assign_roles(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->put(route('admin.members.update', $member), [
                ...$this->validPayload($member),
                'school' => 'Université Rennes 2',
                'bureau_title' => 'Responsable sport',
                'role' => 'admin',
            ])
            ->assertSessionHas('status', 'Fiche du membre mise à jour.');

        $member->refresh();
        $this->assertSame('Université Rennes 2', $member->school);
        $this->assertSame('Responsable sport', $member->bureau_title);
        $this->assertSame('membre', $member->role);
    }

    public function test_admin_assigns_roles(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.members.update', $member), [...$this->validPayload($member), 'role' => 'bureau'])
            ->assertSessionHas('status', 'Fiche du membre mise à jour.');

        $this->assertSame('bureau', $member->fresh()->role);
    }

    public function test_bureau_member_cannot_edit_an_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->put(route('admin.members.update', $admin), [...$this->validPayload($admin), 'status' => 'suspended'])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->isActive());
    }

    public function test_admin_cannot_change_their_own_role_or_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.members.update', $admin), [...$this->validPayload($admin), 'role' => 'membre'])
            ->assertSessionHas('error', 'Vous ne pouvez pas modifier votre propre rôle ou statut.');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_only_an_admin_can_delete_a_member(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->delete(route('admin.members.destroy', $member))
            ->assertForbidden();
        $this->assertModelExists($member);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.members.destroy', $member))
            ->assertRedirect(route('admin.members.index', ['statut' => 'active']));
        $this->assertModelMissing($member);
    }

    public function test_edit_page_shows_the_members_fees(): void
    {
        $member = User::factory()->active()->create();
        $fee = $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 20, 'paid_on' => '2026-10-01', 'method' => 'Espèces']);
        $fee->save();

        $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.members.edit', $member))
            ->assertOk()
            ->assertSee('2026-2027 · 20,00 €');
    }

    public function test_export_lists_members_with_their_fee_status_for_the_season(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $paid = User::factory()->active()->create(['name' => 'Adhérent Àjour']);
        $paid->membershipFees()->make(['season' => MembershipFee::currentSeason(), 'amount' => 20, 'paid_on' => now(), 'method' => 'Virement'])->save();
        User::factory()->active()->create(['name' => 'Adhérent Enretard']);

        $csv = $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.members.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Cotisation 2026-2027', $csv);
        $this->assertMatchesRegularExpression('/"Adhérent Àjour";.*;Payée;/', $csv);
        $this->assertMatchesRegularExpression('/"Adhérent Enretard";.*;"Non payée";/', $csv);
    }

    public function test_member_list_downloads_as_a_pdf_with_every_account(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        User::factory()->active()->create(['name' => 'Membre Actif']);
        User::factory()->create(['name' => 'Inscription En Attente']);
        View::composer('pdf.members', function ($view) use (&$data) {
            $data = $view->getData();
        });

        $response = $this->actingAs(User::factory()->bureau()->create(['name' => 'Bureau Export']))
            ->get(route('admin.members.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('membres-ag35-2026-10-31.pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertEqualsCanonicalizing(['Membre Actif', 'Inscription En Attente', 'Bureau Export'], $data['members']->pluck('name')->all());
        $this->assertSame('2026-2027', $data['season']);
    }

    public function test_export_marks_partial_payments_with_the_amount_paid(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $member = User::factory()->active()->create(['name' => 'Adhérent Partiel']);
        $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 10, 'paid_on' => now(), 'method' => 'Espèces'])->save();

        $csv = $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.members.export'))
            ->streamedContent();

        $this->assertMatchesRegularExpression('/"Adhérent Partiel";.*;"Partielle \\(10,00 €\\)";/', $csv);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(User $member): array
    {
        return [
            'name' => $member->name,
            'email' => $member->email,
            'status' => $member->status,
        ];
    }
}
