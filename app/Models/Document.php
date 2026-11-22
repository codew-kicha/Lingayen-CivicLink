<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Document extends Model
{
    public static function label(string $type): string
    {
        return config("document_types.{$type}.label", Str::headline($type));
    }

    /** Whether a document of this type has an expiry date (not a receipt or the DOLE/SEC certificate). */
    public static function expires(string $type): bool
    {
        return config("document_types.{$type}.expires", true) !== false;
    }

    /** @return list<string> */
    public static function requiredTypes(): array
    {
        return array_keys(array_filter(config('document_types'), fn (array $type) => ! $type['optional']));
    }

    protected $fillable = [
        'organization_id', 'application_id', 'document_type', 'file_path',
        'original_filename', 'mime_type', 'expires_at', 'ocr_status', 'ocr_details',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => DateOnly::class,
            'ocr_details' => 'array',
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
