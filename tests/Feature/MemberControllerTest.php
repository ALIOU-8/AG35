<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_lists_active_members_matching_the_search(): void
    {
        $viewer = User::factory()->active()->create();
        User::factory()->active()->create(['name' => 'Fatoumata Bah', 'school' => 'INSA Rennes']);
        User::factory()->active()->create(['name' => 'Alpha Condé', 'school' => 'Rennes School of Business']);
        User::factory()->create(['name' => 'Kadiatou En Attente', 'school' => 'INSA Rennes']);

        $this->actingAs($viewer)
            ->get(route('members.index', ['q' => 'INSA']))
            ->assertOk()
            ->assertSee('Fatoumata Bah')
            ->assertDontSee('Alpha Condé')
            ->assertDontSee('Kadiatou En Attente');
    }

    public function test_directory_filters_by_study_level(): void
    {
        $viewer = User::factory()->active()->create();
        User::factory()->active()->create(['name' => 'Étudiant Master', 'study_level' => 'M2']);
        User::factory()->active()->create(['name' => 'Étudiant Licence', 'study_level' => 'L1']);

        $this->actingAs($viewer)
            ->get(route('members.index', ['niveau' => 'M2']))
            ->assertOk()
            ->assertSee('Étudiant Master')
            ->assertDontSee('Étudiant Licence');
    }

    public function test_profile_shows_the_members_posts(): void
    {
        $member = User::factory()->active()->create(['name' => 'Mamadou Barry']);
        Post::factory()->for($member)->create(['body' => 'Ma première publication']);

        $this->actingAs(User::factory()->active()->create())
            ->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('Mamadou Barry')
            ->assertSee('Ma première publication');
    }

    public function test_pending_member_profile_is_hidden_from_regular_members(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->get(route('members.show', User::factory()->create()))
            ->assertNotFound();
    }

    public function test_bureau_can_view_a_pending_member_profile(): void
    {
        $this->actingAs(User::factory()->bureau()->create())
            ->get(route('members.show', User::factory()->create(['name' => 'Nouvel Inscrit'])))
            ->assertOk()
            ->assertSee('Nouvel Inscrit');
    }
}
