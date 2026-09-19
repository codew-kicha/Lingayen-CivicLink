<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'organization_id', 'application_id', 'document_type', 'file_path',
        'original_filename', 'mime_type', 'expires_at', 'ocr_status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationModel::class, 'application_id');
    }
}
