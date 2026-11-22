<?php

namespace App\Models;

use App\Services\ImageForensics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * A photo of a logged activity (up to three, optional). Kept privately like the sign-in sheet; the
 * fingerprint and camera data are read once, at upload, for the checks (SheetChecks).
 */
class ActivityPhoto extends Model
{
    public const MAX = 3;

    protected $fillable = ['activity_id', 'path', 'filename', 'mime', 'hash', 'phash', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    /** Stores an uploaded photo for the activity with its fingerprint and camera data. */
    public static function store(Activity $activity, UploadedFile $file): self
    {
        $real = $file->getRealPath();

        return $activity->photos()->create([
            'path' => $file->store("activity-photos/{$activity->organization_id}"),
            'filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => (string) $file->getMimeType(),
            'hash' => hash_file('sha256', $real),
            'phash' => ImageForensics::fingerprint($real),
            'metadata' => ImageForensics::metadata($real) ?: null,
        ]);
    }

    public function takenAt(): ?Carbon
    {
        return isset($this->metadata['taken_at']) ? Carbon::parse($this->metadata['taken_at']) : null;
    }

    /** "Taken 12 Oct 2026 · Samsung SM-A155F · 2 km from Lingayen" for the reviewer. */
    public function summary(): string
    {
        $meta = $this->metadata ?? [];
        $parts = array_filter([
            $this->takenAt() ? 'Taken '.$this->takenAt()->format('d M Y, g:i A') : null,
            $meta['camera'] ?? null,
            isset($meta['lat'], $meta['lng']) ? round(ImageForensics::kmFromLingayen($meta['lat'], $meta['lng'])).' km from Lingayen' : null,
            ($editor = ImageForensics::editor($meta['software'] ?? null)) ? "edited in {$editor}" : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'No camera data';
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
