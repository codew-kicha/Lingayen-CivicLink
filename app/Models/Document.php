<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Document extends Model
{
    public static function label(string $type): string
    {
        return config("document_types.{$type}.label", Str::headline($type));
    }

    /** @return list<string> */
    public static function requiredTypes(): array
    {
        return array_keys(array_filter(config('document_types'), fn (array $type) => ! $type['optional']));
    }

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
