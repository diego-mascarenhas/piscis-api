<?php

namespace App\Models;

use App\Enums\AnalysisCaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisCase extends Model
{
    protected $fillable = [
        'team_id',
        'token_hash',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnalysisCaseStatus::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AnalysisDocument::class);
    }
}
