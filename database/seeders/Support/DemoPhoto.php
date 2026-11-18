<?php

namespace Database\Seeders\Support;

/**
 * JPEGs with camera data, for the demo activities and the tests of the automated checks. GD writes
 * JPEGs without EXIF, so the camera data a phone would write (date taken, make and model, editing
 * software, GPS) is added here as a standard Exif APP1 segment that PHP's exif_read_data() reads.
 */
final class DemoPhoto
{
    /**
     * A JPEG of $source (any image GD can open), or of a plain generated picture, scaled to $width.
     *
     * @param  array{taken_at?: string, software?: string, make?: string, model?: string, lat?: float, lng?: float}  $exif
     */
    public static function jpeg(?string $source = null, array $exif = [], int $width = 640, int $quality = 85, int $seed = 1): string
    {
        if ($source) {
            $original = imagecreatefromstring((string) file_get_contents($source));
            $height = (int) round(imagesy($original) * $width / imagesx($original));
            $image = imagecreatetruecolor($width, $height);
            imagecopyresampled($image, $original, 0, 0, 0, 0, $width, $height, imagesx($original), imagesy($original));
            imagedestroy($original);
        } else {
            // Bands and blocks that differ by seed, so two generated pictures don't look alike.
            $height = (int) round($width * 0.75);
            $image = imagecreatetruecolor($width, $height);
            mt_srand($seed);
            for ($i = 0; $i < 24; $i++) {
                $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                imagefilledrectangle($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
            }
            mt_srand();
        }

        ob_start();
        imagejpeg($image, null, $quality);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        return $exif ? self::withExif($jpeg, $exif) : $jpeg;
    }

    /** The JPEG with an Exif segment holding the given camera data, right after its start marker. */
    public static function withExif(string $jpeg, array $exif): string
    {
        $ascii = fn (string $value) => [2, strlen($value) + 1, $value."\0"];
        $rational = fn (float $degrees) => [5, 3, pack('VVVVVV',
            (int) floor($degrees), 1,
            (int) floor(fmod($degrees, 1) * 60), 1,
            (int) round(fmod($degrees * 60, 1) * 60 * 100), 100)];

        $ifd0 = array_filter([
            0x010F => isset($exif['make']) ? $ascii($exif['make']) : null,
            0x0110 => isset($exif['model']) ? $ascii($exif['model']) : null,
            0x0131 => isset($exif['software']) ? $ascii($exif['software']) : null,
        ]);
        $exifIfd = isset($exif['taken_at']) ? [0x9003 => $ascii(str_replace('-', ':', substr($exif['taken_at'], 0, 10)).substr($exif['taken_at'], 10))] : [];
        $gps = isset($exif['lat'], $exif['lng']) ? [
            0x0000 => [1, 4, "\x02\x02\x00\x00"],
            0x0001 => $ascii($exif['lat'] < 0 ? 'S' : 'N'),
            0x0002 => $rational(abs($exif['lat'])),
            0x0003 => $ascii($exif['lng'] < 0 ? 'W' : 'E'),
            0x0004 => $rational(abs($exif['lng'])),
        ] : [];

        // Lay out IFD0, then the Exif and GPS directories after it; pointers are fixed-size, so a
        // first pass with placeholder pointers gives the sizes.
        $pointers = fn (int $exifAt, int $gpsAt) => ($exifIfd ? [0x8769 => [4, 1, pack('V', $exifAt)]] : []) + ($gps ? [0x8825 => [4, 1, pack('V', $gpsAt)]] : []);
        $first = self::ifd($ifd0 + $pointers(0, 0), 8);
        $exifAt = 8 + strlen($first);
        $exifBytes = $exifIfd ? self::ifd($exifIfd, $exifAt) : '';
        $gpsAt = $exifAt + strlen($exifBytes);
        $gpsBytes = $gps ? self::ifd($gps, $gpsAt) : '';

        $tiff = "II*\x00".pack('V', 8).self::ifd($ifd0 + $pointers($exifAt, $gpsAt), 8).$exifBytes.$gpsBytes;
        $app1 = "\xFF\xE1".pack('n', 2 + 6 + strlen($tiff))."Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    /** One directory at $offset: entries sorted by tag, values over four bytes right after it. */
    private static function ifd(array $entries, int $offset): string
    {
        ksort($entries);
        $dataAt = $offset + 2 + 12 * count($entries) + 4;
        $head = pack('v', count($entries));
        $data = '';
        foreach ($entries as $tag => [$type, $count, $value]) {
            $head .= pack('vvV', $tag, $type, $count);
            if (strlen($value) <= 4) {
                $head .= str_pad($value, 4, "\0");
            } else {
                $head .= pack('V', $dataAt + strlen($data));
                $data .= $value.(strlen($value) % 2 ? "\0" : '');
            }
        }

        return $head.pack('V', 0).$data;
    }
}
