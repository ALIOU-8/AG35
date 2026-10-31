<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\DuesReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Saison 2026-2027, cotisation à 20 € (valeur par défaut du contenu du site).
        $this->travelTo(now()->setDate(2026, 10, 31));
    }

    public function test_regular_member_cannot_open_the_dues_dashboard(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->get(route('admin.dues.index'))
            ->assertForbidden();
    }

    public function test_dashboard_lists_active_members_still_owing_with_their_status(): void
    {
        $this->paying(User::factory()->active()->create(['name' => 'Membre À Jour']), 20);
        $this->paying(User::factory()->active()->create(['name' => 'Membre Partiel']), 10);
        User::factory()->active()->create(['name' => 'Membre Impayé']);
        User::factory()->create(['name' => 'Inscription En Attente']);

        $this->actingAs(User::factory()->bureau()->create(['name' => 'Trésorier Bureau']))
            ->get(route('admin.dues.index'))
            ->assertOk()
            ->assertSee('Membre Partiel')
            ->assertSee('reste 10,00 €')
            ->assertSee('Membre Impayé')
            ->assertDontSee('Membre À Jour')
            ->assertDontSee('Inscription En Attente')
            ->assertSee('30,00 €')
            ->assertSee('38 % recouvré');
    }

    public function test_up_to_date_filter_lists_members_who_paid_in_full(): void
    {
        $this->paying(User::factory()->active()->create(['name' => 'Membre À Jour']), 20);
        User::factory()->active()->create(['name' => 'Membre Impayé']);

        $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.dues.index', ['statut' => 'paid']))
            ->assertOk()
            ->assertSee('Membre À Jour')
            ->assertDontSee('Membre Impayé');
    }

    public function test_reminder_tells_the_member_what_remains_to_pay(): void
    {
        $member = $this->paying(User::factory()->active()->create(), 5);

        $this->actingAs(User::factory()->bureau()->create(['name' => 'Fanta Trésorière']))
            ->post(route('admin.dues.remind', $member), ['season' => '2026-2027'])
            ->assertSessionHas('status', "Rappel envoyé à {$member->name}.");

        $data = $member->notifications()->sole()->data;
        $this->assertSame('dues', $data['kind']);
        $this->assertSame('Fanta Trésorière', $data['actor']['name']);
        $this->assertSame('vous rappelle votre cotisation 2026-2027', $data['text']);
        $this->assertSame('Reste à régler : 15,00 €', $data['excerpt']);
    }

    public function test_member_up_to_date_is_not_reminded(): void
    {
        $member = $this->paying(User::factory()->active()->create(), 20);

        $this->actingAs(User::factory()->bureau()->create())
            ->post(route('admin.dues.remind', $member), ['season' => '2026-2027'])
            ->assertSessionHas('error');

        $this->assertSame(0, $member->notifications()->count());
    }

    public function test_reminding_everyone_skips_members_up_to_date_or_reminded_this_week(): void
    {
        $treasurer = User::factory()->bureau()->create();
        $unpaid = User::factory()->active()->create();
        $partial = $this->paying(User::factory()->active()->create(), 10);
        $paid = $this->paying(User::factory()->active()->create(), 20);
        $recentlyReminded = User::factory()->active()->create();
        $recentlyReminded->notify(new DuesReminder($treasurer, '2026-2027', 20));
        $this->travel(2)->days();

        $this->actingAs($treasurer)
            ->post(route('admin.dues.remind-all'), ['season' => '2026-2027'])
            ->assertSessionHas('status', '2 rappels envoyés. 1 membre(s) déjà relancé(s) ces 7 derniers jours.');

        $this->assertSame(1, $unpaid->notifications()->count());
        $this->assertSame('Reste à régler : 10,00 €', $partial->notifications()->sole()->data['excerpt']);
        $this->assertSame(0, $paid->notifications()->count());
        $this->assertSame(1, $recentlyReminded->notifications()->count());
        $this->assertSame(0, $treasurer->notifications()->count());
    }

    public function test_reminding_everyone_requires_a_fee_amount(): void
    {
        Setting::put(['membership_fee' => '']);
        $member = User::factory()->active()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->post(route('admin.dues.remind-all'), ['season' => '2026-2027'])
            ->assertSessionHas('error');

        $this->assertSame(0, $member->notifications()->count());
    }

    public function test_reminder_for_an_unknown_season_is_rejected(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->post(route('admin.dues.remind-all'), ['season' => '1999-2000'])
            ->assertSessionHasErrors('season');
    }

    public function test_payment_log_exports_the_seasons_payments(): void
    {
        $this->paying(User::factory()->active()->create(['name' => 'Ibrahima Sow']), 20);

        $csv = $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.dues.export', ['saison' => '2026-2027']))
            ->assertOk()
            ->assertDownload('cotisations-2026-2027.csv')
            ->streamedContent();

        $this->assertStringContainsString('"Ibrahima Sow"', $csv);
        $this->assertStringContainsString(';20,00;Espèces;', $csv);
    }

    private function paying(User $member, float $amount): User
    {
        $member->membershipFees()->make(['season' => '2026-2027', 'amount' => $amount, 'paid_on' => now(), 'method' => 'Espèces'])->save();

        return $member;
    }
}
