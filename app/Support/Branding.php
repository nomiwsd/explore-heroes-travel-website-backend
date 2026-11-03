<?php

namespace App\Support;

/**
 * ST-02 / ST-03: site branding for emails & invoices, driven by settings.
 * Never returns the BookingCore template vendor's demo values.
 */
class Branding
{
    public const DEFAULT_SITE_NAME = 'Explore Heroes';
    public const DEFAULT_COMPANY_NAME = 'ExploreHeroes (Pvt) Ltd';
    public const DEFAULT_COMPANY_ADDRESS = '87 Dutugemunu St, Dehiwala-Mount Lavinia, Sri Lanka';
    public const DEFAULT_COMPANY_SITE = 'exploreheroes.com';

    protected static function setting($key, $default = '')
    {
        if (!function_exists('setting_item')) {
            return $default;
        }
        try {
            $v = setting_item($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
        return is_string($v) ? trim($v) : $v;
    }

    public static function isVendorDemo($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }
        return (bool) preg_match('/booking\s*core|bookingcore/i', $value);
    }

    public static function siteName(): string
    {
        foreach (['site_title', 'site_name'] as $key) {
            $v = static::setting($key);
            if (is_string($v) && $v !== '' && !static::isVendorDemo($v)) {
                return $v;
            }
        }
        $app = (string) config('app.name');
        if ($app !== '' && !static::isVendorDemo($app) && strcasecmp($app, 'Laravel') !== 0) {
            return $app;
        }
        return self::DEFAULT_SITE_NAME;
    }

    /**
     * Replace vendor leftovers in admin-provided HTML and expand tokens:
     * [site_name] / {site_name} and [year] / {year}. Hardcoded "© 2019" years
     * are bumped to the current year.
     */
    public static function brandHtml($html)
    {
        if (!is_string($html) || $html === '') {
            return $html;
        }
        $site = e(static::siteName());
        $year = date('Y');
        $html = str_ireplace(['[site_name]', '{site_name}', '[year]', '{year}'], [$site, $site, $year, $year], $html);
        $html = preg_replace('/booking\s*core/i', $site, $html);
        $html = preg_replace('/(&copy;|©|copyright)(\s*)(19|20)\d{2}\b/iu', '${1}${2}' . $year, $html);
        return $html;
    }

    public static function emailHeaderHtml($locale = null): string
    {
        $header = '';
        if (function_exists('setting_item_with_lang')) {
            try {
                $header = (string) setting_item_with_lang('email_header', $locale ?: '');
            } catch (\Throwable $e) {
                $header = '';
            }
        }
        if (trim(strip_tags($header)) === '' && stripos($header, '<img') === false) {
            return sprintf('<h1 class="site-title" style="text-align: center">%s</h1>', e(static::siteName()));
        }
        return static::brandHtml($header);
    }

    public static function emailFooterHtml($locale = null): string
    {
        $footer = '';
        if (function_exists('setting_item_with_lang')) {
            try {
                $footer = (string) setting_item_with_lang('email_footer', $locale ?: '');
            } catch (\Throwable $e) {
                $footer = '';
            }
        }
        if (trim(strip_tags($footer)) === '') {
            return sprintf(
                '<p style="text-align: center">&copy; %s %s. All rights reserved</p>',
                date('Y'),
                e(static::siteName())
            );
        }
        return static::brandHtml($footer);
    }

    /**
     * ST-03: invoice company block (HTML). Uses the `invoice_company_info`
     * setting unless it still holds the vendor demo text.
     */
    public static function invoiceCompanyInfoHtml(): string
    {
        $info = (string) static::setting('invoice_company_info');
        if (trim(strip_tags($info)) !== '' && !static::isVendorDemo($info)
            && stripos($info, 'Ha Noi') === false) {
            return $info;
        }
        $name = static::setting('invoice_company_name') ?: self::DEFAULT_COMPANY_NAME;
        $address = static::setting('invoice_company_address') ?: self::DEFAULT_COMPANY_ADDRESS;
        $site = static::setting('invoice_company_site') ?: self::DEFAULT_COMPANY_SITE;
        if (static::isVendorDemo($name)) {
            $name = self::DEFAULT_COMPANY_NAME;
        }
        return sprintf(
            '<p><span style="font-size: 14pt;"><strong>%s</strong></span></p><p>%s</p><p>%s</p>',
            e($name),
            e($address),
            e($site)
        );
    }

    public static function defaultInvoiceCompanyInfoHtml(): string
    {
        return sprintf(
            '<p><span style="font-size: 14pt;"><strong>%s</strong></span></p><p>%s</p><p>%s</p>',
            e(self::DEFAULT_COMPANY_NAME),
            e(self::DEFAULT_COMPANY_ADDRESS),
            e(self::DEFAULT_COMPANY_SITE)
        );
    }
}
