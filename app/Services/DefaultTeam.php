<?php

namespace App\Services;

use App\Models\Team;

class DefaultTeam
{
    public function id(): int
    {
        $team = Team::query()->firstOrCreate(
            ['name' => (string) config('piscis.default_team_name')],
            ['personal_team' => false],
        );

        return $team->id;
    }
}
