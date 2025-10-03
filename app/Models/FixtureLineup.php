<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixtureLineup extends Model
{
    protected $fillable = [
        'fixture_id',
        'team_id',
        'team_name',
        'formation',
        'starting_xi',
        'substitutes',
        'coach',
        'raw_data'
    ];

    protected $casts = [
        'starting_xi' => 'array',
        'substitutes' => 'array',
        'coach' => 'array',
        'raw_data' => 'array'
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    /**
     * Get all player IDs in this lineup (starting XI + substitutes)
     */
    public function getAllPlayerIds(): array
    {
        $startingPlayerIds = collect($this->starting_xi)->pluck('player.id')->toArray();
        $substitutePlayerIds = collect($this->substitutes)->pluck('player.id')->toArray();

        return array_merge($startingPlayerIds, $substitutePlayerIds);
    }

    /**
     * Get starting XI player IDs only
     */
    public function getStartingPlayerIds(): array
    {
        return collect($this->starting_xi)->pluck('player.id')->toArray();
    }

    /**
     * Get substitute player IDs only
     */
    public function getSubstitutePlayerIds(): array
    {
        return collect($this->substitutes)->pluck('player.id')->toArray();
    }

    /**
     * Check if a player is in the starting XI
     */
    public function isPlayerStarting(int $playerId): bool
    {
        return in_array($playerId, $this->getStartingPlayerIds());
    }

    /**
     * Check if a player is a substitute
     */
    public function isPlayerSubstitute(int $playerId): bool
    {
        return in_array($playerId, $this->getSubstitutePlayerIds());
    }

    /**
     * Check if a player is in the lineup (starting or substitute)
     */
    public function hasPlayer(int $playerId): bool
    {
        return in_array($playerId, $this->getAllPlayerIds());
    }

    /**
     * Get player position in the lineup
     */
    public function getPlayerPosition(int $playerId): ?string
    {
        // Check starting XI
        foreach ($this->starting_xi as $player) {
            if ($player['player']['id'] == $playerId) {
                return $player['player']['pos'] ?? null;
            }
        }

        // Check substitutes
        foreach ($this->substitutes as $player) {
            if ($player['player']['id'] == $playerId) {
                return $player['player']['pos'] ?? null;
            }
        }

        return null;
    }
}
