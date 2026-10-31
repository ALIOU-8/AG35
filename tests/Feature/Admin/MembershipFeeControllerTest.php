<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipFeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bureau_records_a_fee_and_is_kept_as_recorder(): void
    {
        $treasurer = User::factory()->bureau('Trésorier')->create();
        $member = User::factory()->active()->create();

        $this->actingAs($treasurer)
            ->post(route('admin.fees.store', $member), [
                'season' => '2026-2027',
                'amount' => '20',
                'paid_on' => '2026-10-15',
                'method' => 'Lydia / Wero',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', "Paiement de {$member->name} enregistré.");

        $this->assertDatabaseHas('membership_fees', [
            'user_id' => $member->id,
            'season' => '2026-2027',
            'method' => 'Lydia / Wero',
            'recorded_by' => $treasurer->id,
        ]);
    }

    public function test_recording_a_payment_sends_a_receipt_to_the_member(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs(User::factory()->bureau('Trésorier')->create(['name' => 'Alpha Diallo']))
            ->post(route('admin.fees.store', $member), ['season' => '2026-2027', 'amount' => '20', 'paid_on' => '2026-10-15', 'method' => 'Virement']);

        $receipt = $member->notifications()->sole()->data;
        $this->assertSame('Alpha Diallo', $receipt['actor']['name']);
        $this->assertSame('a enregistré votre paiement de cotisation 2026-2027', $receipt['text']);
        $this->assertSame('20,00 € · Virement', $receipt['excerpt']);
    }

    public function test_deleting_a_payment_withdraws_its_receipt(): void
    {
        $member = User::factory()->active()->create();
        $bureau = User::factory()->bureau()->create();
        $this->actingAs($bureau)->post(route('admin.fees.store', $member), ['season' => '2026-2027', 'amount' => '20', 'paid_on' => '2026-10-15', 'method' => 'Espèces']);

        $this->actingAs($bureau)->delete(route('admin.fees.destroy', $member->membershipFees()->sole()))->assertRedirect();

        $this->assertSame(0, $member->notifications()->count());
    }

    public function test_fee_with_a_malformed_season_or_unknown_method_is_rejected(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->post(route('admin.fees.store', User::factory()->active()->create()), [
                'season' => '2026',
                'amount' => '20',
                'paid_on' => '2026-10-15',
                'method' => 'Bitcoin',
            ])
            ->assertSessionHasErrors(['season', 'method']);

        $this->assertDatabaseCount('membership_fees', 0);
    }

    public function test_regular_member_cannot_record_fees(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->post(route('admin.fees.store', $member), ['season' => '2026-2027', 'amount' => '20', 'paid_on' => '2026-10-15', 'method' => 'Espèces'])
            ->assertForbidden();
    }

    public function test_bureau_deletes_a_fee(): void
    {
        $member = User::factory()->active()->create();
        $fee = $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 20, 'paid_on' => '2026-10-01', 'method' => 'Espèces']);
        $fee->save();

        $this->actingAs(User::factory()->bureau()->create())
            ->delete(route('admin.fees.destroy', $fee))
            ->assertRedirect();

        $this->assertModelMissing($fee);
    }
}
