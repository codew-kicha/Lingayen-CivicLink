<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * What an image says about itself, read with PHP's own GD and EXIF extensions (no outside service,
 * no extra package). Used by the activity checks (SheetChecks) to help the office tell a real
 * activity photo from a reused or edited one:
 *
 * - fingerprint(): a 64-bit difference hash. Two images that look the same (the same photo resized,
 *   re-saved, lightly cropped or recompressed by a chat app) have fingerprints a few bits apart,
 *   which an exact file hash cannot catch.
 * - metadata(): the camera data a phone writes (date taken, editing software, camera, GPS).
 *
 * Every method fails soft: an image GD can't open, or a host without the extensions, gives null or
 * an empty result, and the checks carry on without it.
 */
class ImageForensics
{
    /** Fingerprints this many bits apart (of 64) or fewer are the same picture. */
    public const SAME_PICTURE = 6;

    /** Lingayen's town proper (municipal hall), for the GPS check. */
    public const LINGAYEN = [16.0219, 120.2310];

    /** Farther than this from the town proper is outside Lingayen and its barangays. */
    public const NEAR_KM = 25;

    /** Software tags that mean the picture was edited after it was taken. */
    private const EDITORS = ['photoshop', 'lightroom', 'gimp', 'canva', 'picsart', 'snapseed', 'facetune', 'pixlr', 'fotor', 'meitu', 'affinity', 'paint.net'];

    /** Larger images are skipped (decoding one would use too much memory on shared hosting). */
    private const MAX_PIXELS = 40_000_000;

    /** A 16-hex-digit difference hash of the image, or null when it can't be read. */
    public static function fingerprint(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! is_file($path)) {
            return null;
        }
        $size = @getimagesize($path);
        if (! $size || $size[0] * $size[1] > self::MAX_PIXELS) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) {
            return null;
        }

        // 9x8 grey thumbnail; each bit says whether a pixel is brighter than its right neighbour.
        $small = imagecreatetruecolor(9, 8);
        imagecopyresampled($small, $image, 0, 0, 0, 0, 9, 8, imagesx($image), imagesy($image));
        imagedestroy($image);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            $row = [];
            for ($x = 0; $x < 9; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $row[] = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            }
            for ($x = 0; $x < 8; $x++) {
                $bits .= $row[$x] > $row[$x + 1] ? '1' : '0';
            }
        }
        imagedestroy($small);

        return implode('', array_map(fn (string $nibble) => dechex(bindec($nibble)), str_split($bits, 4)));
    }

    /** How many of the 64 bits differ between two fingerprints. */
    public static function distance(string $a, string $b): int
    {
        $count = 0;
        foreach ([0, 8] as $offset) {
            $count += substr_count(decbin(hexdec(substr($a, $offset, 8)) ^ hexdec(substr($b, $offset, 8))), '1');
        }

        return $count;
    }

    /**
     * The camera data of a JPEG: taken_at (Y-m-d H:i:s), software, camera, lat, lng. Empty when there
     * is none (a PNG, a screenshot, or a photo passed through Messenger or Facebook, which strip it).
     *
     * @return array{taken_at?: string, software?: string, camera?: string, lat?: float, lng?: float}
     */
    public static function metadata(string $path): array
    {
        if (! function_exists('exif_read_data') || ! is_file($path)) {
            return [];
        }
        $exif = @exif_read_data($path);
        if (! is_array($exif)) {
            return [];
        }

        $data = [];
        $taken = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? null;
        if (is_string($taken) && ($date = rescue(fn () => Carbon::createFromFormat('Y:m:d H:i:s', trim($taken)), null, false))) {
            $data['taken_at'] = $date->format('Y-m-d H:i:s');
        }
        if (filled($exif['Software'] ?? null) && is_string($exif['Software'])) {
            $data['software'] = mb_substr(trim($exif['Software']), 0, 100);
        }
        $camera = trim(implode(' ', array_filter([is_string($exif['Make'] ?? null) ? $exif['Make'] : null, is_string($exif['Model'] ?? null) ? $exif['Model'] : null])));
        if ($camera !== '') {
            $data['camera'] = mb_substr($camera, 0, 100);
        }
        $lat = self::coordinate($exif['GPSLatitude'] ?? null, $exif['GPSLatitudeRef'] ?? 'N');
        $lng = self::coordinate($exif['GPSLongitude'] ?? null, $exif['GPSLongitudeRef'] ?? 'E');
        if ($lat !== null && $lng !== null && ($lat != 0.0 || $lng != 0.0)) {
            [$data['lat'], $data['lng']] = [round($lat, 5), round($lng, 5)];
        }

        return $data;
    }

    /** The editing program named in the software tag, if it is one. */
    public static function editor(?string $software): ?string
    {
        $software = mb_strtolower((string) $software);
        foreach (self::EDITORS as $editor) {
            if (str_contains($software, $editor)) {
                return $editor === 'paint.net' ? 'Paint.NET' : ucfirst($editor);
            }
        }

        return null;
    }

    /** Kilometres from Lingayen's town proper (haversine). */
    public static function kmFromLingayen(float $lat, float $lng): float
    {
        [$lat0, $lng0] = array_map('deg2rad', self::LINGAYEN);
        [$lat1, $lng1] = [deg2rad($lat), deg2rad($lng)];
        $a = sin(($lat1 - $lat0) / 2) ** 2 + cos($lat0) * cos($lat1) * sin(($lng1 - $lng0) / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($a)));
    }

    /** ["16/1", "1/1", "1800/100"] and N/S/E/W as signed decimal degrees. */
    private static function coordinate(mixed $parts, mixed $ref): ?float
    {
        if (! is_array($parts) || count($parts) !== 3) {
            return null;
        }
        $values = array_map(function ($part) {
            if (is_string($part) && str_contains($part, '/')) {
                [$n, $d] = array_map('floatval', explode('/', $part, 2));

                return $d == 0.0 ? null : $n / $d;
            }

            return is_numeric($part) ? (float) $part : null;
        }, array_values($parts));
        if (in_array(null, $values, true)) {
            return null;
        }
        $degrees = $values[0] + $values[1] / 60 + $values[2] / 3600;

        return in_array(strtoupper((string) $ref), ['S', 'W'], true) ? -$degrees : $degrees;
    }
}
