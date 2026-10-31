<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\MemberController;
use App\Models\Meeting;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration. Les événements de modèle restent actifs : ils génèrent les uuid publics.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'bureau_title' => 'Président',
            'bureau_order' => 1,
        ]);

        $members = User::factory(8)->active()->create()
            ->push(User::factory()->bureau('Trésorière')->create(['bureau_order' => 2]), $admin);

        $members->each(fn (User $member) => MemberController::activate($member));

        User::factory(2)->create();

        Post::factory(6)->recycle($members)->create();
        Post::factory(3)->public()->recycle($admin)->create();

        Meeting::factory()->recycle($admin)->create(['title' => 'Réunion du bureau']);
    }
}
