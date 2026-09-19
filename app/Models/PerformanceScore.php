<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceScore extends Model
{
    protected $fillable = [
        'organization_id', 'period_start', 'period_end', 'activity_frequency',
        'community_reach', 'compliance_timeliness', 'document_currency', 'total_score', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'computed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
