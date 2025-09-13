<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Peer;
use App\Models\Tournament;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();
        Peer::factory(40)->create();
        Admin::updateOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'phone' => '1234567890',
            'password' => 'password',
        ]);

        $this->call([TournamentSeeder::class]);
    }
}
