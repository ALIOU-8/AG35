<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_shows_default_texts(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(Setting::DEFAULTS['slogan']);
    }

    public function test_saved_texts_appear_on_the_public_site(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->put(route('admin.settings.update'), [
                'slogan' => 'Ensemble, plus forts à Rennes',
                'contact_email' => 'bureau@ag35.fr',
                'instagram_url' => 'https://instagram.com/ag35',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Contenu du site mis à jour.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ensemble, plus forts à Rennes')
            ->assertSee('bureau@ag35.fr')
            ->assertSee('https://instagram.com/ag35');
    }

    public function test_invalid_email_and_social_link_are_rejected(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->put(route('admin.settings.update'), ['contact_email' => 'pas-un-email', 'facebook_url' => 'javascript:alert(1)', 'membership_fee' => 'vingt euros'])
            ->assertSessionHasErrors(['contact_email', 'facebook_url', 'membership_fee']);

        $this->assertSame(Setting::DEFAULTS['contact_email'], Setting::get('contact_email'));
    }
}
