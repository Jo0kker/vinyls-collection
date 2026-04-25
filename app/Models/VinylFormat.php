<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VinylFormat extends Model
{
    public const AUTRE = 14;
    public const LP = 3;
    public const LP_2 = 4;
    public const LP_3 = 5;
    public const LP_4 = 6;
    public const LP_5 = 7;
    public const LP_6 = 8;
    public const QUARANTE_CINQ_T = 1;
    public const MAXI_45T = 2;
    public const SOIXANTE_DIX_HUIT_T = 9;
    public const CD = 10;
    public const K7 = 11;
    public const DVD = 12;
    public const VHS = 13;
    public const EP = 15;
    public const MAXI_33T = 16;
    public const LP_25CM = 17;

    protected $fillable = ['label', 'slug', 'sort_order', 'active'];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function vinyls()
    {
        return $this->hasMany(Vinyl::class, 'vinyl_format');
    }

    public static function allCached(): array
    {
        return Cache::remember('vinyl_formats:list', 3600, function () {
            return static::where('active', true)
                ->orderBy('sort_order')
                ->get(['id', 'label', 'slug'])
                ->toArray();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('vinyl_formats:list');
    }

    /**
     * Maps the Discogs API `formats` array to an internal format ID.
     * Discogs payload example:
     *   [{ "name": "Vinyl", "qty": "1", "descriptions": ["LP", "Album"] }]
     */
    public static function fromDiscogsFormats(?array $formats): int
    {
        if (empty($formats) || !is_array($formats)) {
            return self::AUTRE;
        }

        $first = $formats[0] ?? [];
        $name = $first['name'] ?? '';
        $descriptions = array_map('strval', $first['descriptions'] ?? []);
        $qty = (int) ($first['qty'] ?? 1);

        $hasDesc = fn (string $needle) => in_array($needle, $descriptions, true);

        return match (true) {
            $name === 'CD' => self::CD,
            $name === 'Cassette' => self::K7,
            $name === 'DVD' => self::DVD,
            $name === 'VHS' => self::VHS,
            $name === 'Vinyl' => self::resolveVinylSubformat($descriptions, $qty, $hasDesc),
            default => self::AUTRE,
        };
    }

    private static function resolveVinylSubformat(array $descriptions, int $qty, callable $hasDesc): int
    {
        if ($hasDesc('78 RPM')) {
            return self::SOIXANTE_DIX_HUIT_T;
        }

        if ($hasDesc('7"') || $hasDesc('45 RPM')) {
            return $hasDesc('Maxi-Single') ? self::MAXI_45T : self::QUARANTE_CINQ_T;
        }

        if ($hasDesc('10"')) {
            return self::LP_25CM;
        }

        if ($hasDesc('EP')) {
            return self::EP;
        }

        if ($hasDesc('Maxi-Single')) {
            return self::MAXI_33T;
        }

        if ($hasDesc('12"') || $hasDesc('LP') || $hasDesc('Album')) {
            return match ($qty) {
                2 => self::LP_2,
                3 => self::LP_3,
                4 => self::LP_4,
                5 => self::LP_5,
                6 => self::LP_6,
                default => self::LP,
            };
        }

        return self::LP;
    }
}
