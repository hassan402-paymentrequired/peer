<?php

namespace Database\Seeders;

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

       $this->call([TournamentSeeder::class]);
    }
}
