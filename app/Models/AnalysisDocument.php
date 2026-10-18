<?php

namespace App\Models;

use App\Enums\AnalysisDocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisDocument extends Model
{
    protected $fillable = [
        'analysis_case_id',
        'original_name',
        'stored_path',
        'sha256',
        'mime',
        'size',
        'status',
        'extracted_text',
        'redaction_notes',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnalysisDocumentStatus::class,
            'redaction_notes' => 'array',
            'size' => 'integer',
        ];
    }

    public function analysisCase(): BelongsTo
    {
        return $this->belongsTo(AnalysisCase::class);
    }
}
