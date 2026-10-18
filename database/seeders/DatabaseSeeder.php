<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Team::query()->firstOrCreate(
            ['name' => (string) config('piscis.default_team_name')],
            ['personal_team' => false],
        );
    }
}
