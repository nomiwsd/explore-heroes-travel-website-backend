<?php

namespace Modules\Tour\Support;

use Illuminate\Support\Facades\Schema;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationTranslation;
use Modules\Tour\Models\Tour;
use Modules\Tour\Models\TourLocation;

/**
 * Maps a tour's free-text `cities_covered` strings onto Location (destination)
 * rows and answers "which published tours belong to destination X".
 *
 * A tour belongs to a destination when ANY of these hold:
 *   - a bc_tour_locations pivot row links them
 *   - tours.location_id = destination id
 *   - one of the tour's cities_covered strings matches the destination's
 *     name / slug (or any translated name / slug), case-insensitive + trimmed
 *
 * Results are memoised per request (static); the data set is small (~20 tours).
 */
class TourDestinations
{
    /** @var array<string,int>|null normalized key => location id */
    protected static $index = null;

    /** @var array<int,Location>|null */
    protected static $locations = null;

    /** @var array<int,int[]>|null location id => tour ids */
    protected static $locationTourMap = null;

    /** @var array<string,array<int,LocationTranslation>> */
    protected static $translationCache = [];

    public static function flush(): void
    {
        static::$index = null;
        static::$locations = null;
        static::$locationTourMap = null;
        static::$translationCache = [];
    }

    public static function normalize($value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return mb_strtolower((string) $value, 'UTF-8');
    }

    /**
     * Normalise the cities_covered attribute (array cast, JSON string or CSV) to string[].
     */
    public static function citiesList($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['name'] ?? $item['title'] ?? $item['value'] ?? null;
            }
            if (!is_string($item) && !is_numeric($item)) {
                continue;
            }
            $item = trim((string) $item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }

    protected static function buildIndex(): void
    {
        if (static::$index !== null) {
            return;
        }
        static::$index = [];
        static::$locations = [];

        $locations = Location::where('status', 'publish')->get(['id', 'name', 'slug']);
        foreach ($locations as $loc) {
            static::$locations[(int) $loc->id] = $loc;
        }
        // Origin name/slug take precedence over translation names
        foreach ($locations as $loc) {
            foreach ([$loc->name, $loc->slug] as $key) {
                $k = static::normalize($key);
                if ($k !== '' && !isset(static::$index[$k])) {
                    static::$index[$k] = (int) $loc->id;
                }
            }
        }

        try {
            $ids = array_keys(static::$locations);
            if ($ids) {
                $cols = ['origin_id', 'name'];
                if (Schema::hasColumn('bc_location_translations', 'slug')) {
                    $cols[] = 'slug';
                }
                $trs = LocationTranslation::whereIn('origin_id', $ids)->get($cols);
                foreach ($trs as $tr) {
                    foreach ([$tr->name, $tr->slug ?? null] as $key) {
                        $k = static::normalize($key);
                        if ($k !== '' && !isset(static::$index[$k])) {
                            static::$index[$k] = (int) $tr->origin_id;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // translation table problem: origin matching still works
        }
    }

    /**
     * @return Location|null the published location matching a free-text city string
     */
    public static function match($city)
    {
        static::buildIndex();
        $k = static::normalize($city);
        if ($k === '' || !isset(static::$index[$k])) {
            return null;
        }
        return static::$locations[static::$index[$k]] ?? null;
    }

    public static function matchesLocation($city, $locationId): bool
    {
        $loc = static::match($city);
        return $loc && (int) $loc->id === (int) $locationId;
    }

    protected static function translationsFor(string $lang): array
    {
        if (!isset(static::$translationCache[$lang])) {
            static::buildIndex();
            $map = [];
            try {
                $ids = array_keys(static::$locations);
                if ($ids) {
                    foreach (LocationTranslation::whereIn('origin_id', $ids)->where('locale', $lang)->get() as $tr) {
                        $map[(int) $tr->origin_id] = $tr;
                    }
                }
            } catch (\Throwable $e) {
            }
            static::$translationCache[$lang] = $map;
        }
        return static::$translationCache[$lang];
    }

    /**
     * Translated destination name for a location id (falls back to origin name).
     */
    public static function translatedName($location, $lang = null)
    {
        if (!$location) {
            return null;
        }
        if ($lang && !is_default_lang($lang)) {
            $tr = static::translationsFor($lang)[(int) $location->id] ?? null;
            if (!$tr) {
                $tr = method_exists($location, 'forceTranslate') ? $location->forceTranslate($lang) : null;
            }
            if ($tr && !empty($tr->name)) {
                return $tr->name;
            }
        }
        return $location->name;
    }

    /**
     * Build the `cities` array for a tour: [{ name, original, slug, id }]
     *
     * @param Tour|array|string|null $tour a Tour model or a raw cities_covered value
     */
    public static function cities($tour, $lang = null): array
    {
        $raw = is_object($tour) ? $tour->cities_covered : $tour;
        $needTranslation = $lang && !is_default_lang($lang);
        $translations = $needTranslation ? static::translationsFor($lang) : [];

        $out = [];
        foreach (static::citiesList($raw) as $city) {
            $loc = static::match($city);
            $name = $city;
            $slug = null;
            $id = null;
            if ($loc) {
                $id = (int) $loc->id;
                $slug = $loc->slug;
                // Use the destination's canonical name ("Galle", not "galle ").
                if (!empty($loc->name)) {
                    $name = $loc->name;
                }
                $tr = $translations[$id] ?? null;
                if ($tr) {
                    if (!empty($tr->name)) {
                        $name = $tr->name;
                    }
                    if (!empty($tr->slug)) {
                        $slug = $tr->slug;
                    }
                }
            }
            $out[] = [
                'name' => $name,
                'original' => $city,
                'slug' => $slug,
                'id' => $id,
            ];
        }
        return $out;
    }

    /**
     * location id => unique published tour ids (pivot + location_id + cities_covered).
     */
    public static function locationTourMap(): array
    {
        if (static::$locationTourMap !== null) {
            return static::$locationTourMap;
        }
        static::buildIndex();
        $map = [];

        $tours = Tour::where('status', 'publish')->get(['id', 'location_id', 'cities_covered']);
        $publishedIds = [];
        foreach ($tours as $tour) {
            $tid = (int) $tour->id;
            $publishedIds[$tid] = true;
            if ($tour->location_id) {
                $map[(int) $tour->location_id][$tid] = true;
            }
            foreach (static::citiesList($tour->cities_covered) as $city) {
                $loc = static::match($city);
                if ($loc) {
                    $map[(int) $loc->id][$tid] = true;
                }
            }
        }

        try {
            foreach (TourLocation::get(['tour_id', 'location_id']) as $row) {
                if ($row->location_id && isset($publishedIds[(int) $row->tour_id])) {
                    $map[(int) $row->location_id][(int) $row->tour_id] = true;
                }
            }
        } catch (\Throwable $e) {
        }

        foreach ($map as $locId => $set) {
            $map[$locId] = array_keys($set);
        }
        return static::$locationTourMap = $map;
    }

    /**
     * @param int|int[] $locationIds
     * @return int[] published tour ids belonging to any of the given locations
     */
    public static function tourIdsForLocations($locationIds): array
    {
        $map = static::locationTourMap();
        $ids = [];
        foreach ((array) $locationIds as $locId) {
            if (!is_numeric($locId)) {
                continue;
            }
            foreach ($map[(int) $locId] ?? [] as $tid) {
                $ids[$tid] = true;
            }
        }
        return array_keys($ids);
    }

    public static function countForLocation($locationId): int
    {
        return count(static::locationTourMap()[(int) $locationId] ?? []);
    }
}
