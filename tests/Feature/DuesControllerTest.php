<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_what_remains_to_pay_and_how_to_pay(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $member = User::factory()->active()->create();
        $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 5, 'paid_on' => now(), 'method' => 'Espèces'])->save();
        User::factory()->bureau('Trésorière')->create(['name' => 'Kadiatou Barry']);

        $this->actingAs($member)
            ->get(route('dues.show'))
            ->assertOk()
            ->assertSee('Saison 2026-2027')
            ->assertSee('Reste à régler : 15,00 €')
            ->assertSee('déjà versé : 5,00 €')
            ->assertSee('Réglez votre cotisation auprès du trésorier')
            ->assertSee('Kadiatou Barry');
    }

    public function test_member_up_to_date_sees_a_confirmation(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $member = User::factory()->active()->create();
        $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 20, 'paid_on' => now(), 'method' => 'Virement'])->save();

        $this->actingAs($member)
            ->get(route('dues.show'))
            ->assertOk()
            ->assertSee('Cotisation réglée')
            ->assertDontSee('Comment payer');
    }
}
