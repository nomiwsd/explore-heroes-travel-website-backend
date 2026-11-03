<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NAME = '2026_11_03_000003_rebrand_bookingcore_settings';

    /**
     * ST-02 / ST-03: replace BookingCore template vendor values that are still
     * stored in core_settings. Only rows whose value still contains the vendor
     * demo text are touched; anything the admin customised is left alone.
     * Previous values are kept in bc_settings_backups and restored by down().
     */
    public function up(): void
    {
        if (!Schema::hasTable('core_settings')) {
            return;
        }

        $isVendor = function ($val) {
            return is_string($val) && preg_match('/booking\s*core|bookingcore|ha noi, viet nam/i', $val);
        };

        $companyHtml = '<p><span style="font-size: 14pt;"><strong>ExploreHeroes (Pvt) Ltd</strong></span></p>'
            . '<p>87 Dutugemunu St, Dehiwala-Mount Lavinia, Sri Lanka</p><p>exploreheroes.com</p>';

        $replacements = [
            'site_title'           => 'Explore Heroes',
            'email_from_name'      => 'Explore Heroes',
            // empty => config('mail.from.address') (MAIL_FROM_ADDRESS) is used
            'email_from_address'   => '',
            // empty => header/footer are generated from site name + current year
            'email_header'         => '',
            'email_footer'         => '',
            'invoice_company_info' => $companyHtml,
        ];

        foreach ($replacements as $name => $newVal) {
            $rows = DB::table('core_settings')->where('name', 'like', $name . '%')->get(['id', 'name', 'val']);
            foreach ($rows as $row) {
                // exact key or legacy per-locale key (e.g. email_footer_ar)
                if ($row->name !== $name && !preg_match('/^' . preg_quote($name, '/') . '_[a-z]{2}$/', $row->name)) {
                    continue;
                }
                if ($isVendor($row->val)) {
                    \App\Support\SettingsBackup::update(self::NAME, $row, $newVal);
                }
            }
        }
    }

    public function down(): void
    {
        \App\Support\SettingsBackup::restore(self::NAME);
    }
};
