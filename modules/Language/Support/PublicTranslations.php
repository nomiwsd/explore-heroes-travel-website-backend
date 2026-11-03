<?php

namespace Modules\Language\Support;

/**
 * Serves the compiled frontend translation JSON for a locale.
 *
 * Read order (first existing, parseable file wins):
 *   1. storage/app/translations/{locale}.json   (written by admin "Build")
 *   2. lang/{locale}.json
 *   3. resources/lang/{locale}.json
 *   4. public/locales/{locale}.json             (git-tracked, may be stale)
 *   5. {}
 */
class PublicTranslations
{
    public const LOCALE_PATTERN = '/^[a-z]{2}(-[A-Za-z]{2})?$/';

    public static function isValidLocale($locale): bool
    {
        return is_string($locale) && preg_match(self::LOCALE_PATTERN, $locale) === 1;
    }

    public static function candidates(string $locale): array
    {
        return [
            storage_path('app/translations/' . $locale . '.json'),
            base_path('lang/' . $locale . '.json'),
            base_path('resources/lang/' . $locale . '.json'),
            base_path('public/locales/' . $locale . '.json'),
        ];
    }

    public static function response($locale)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'public, max-age=60',
        ];

        if (!self::isValidLocale($locale)) {
            return response()->json(['message' => 'Invalid locale'], 400);
        }

        foreach (self::candidates($locale) as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $content = @file_get_contents($file);
            if ($content === false || trim($content) === '') {
                continue;
            }
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            return response($content, 200, $headers);
        }

        return response('{}', 200, $headers);
    }
}
