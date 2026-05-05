<?php
/**
 * Created by PhpStorm.
 * User: dunglinh
 * Date: 6/8/19
 * Time: 22:06
 */

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasTranslations
{

    /**
     * Class name for translation, default is current class
     * @var
     */
    protected $translation_class;

    /**
     * @param false $locale
     * @return boolean
     */
    public function saveTranslation($locale = false,$saveSEO = false){
        if(is_enable_multi_lang()){
            $class = $this->getTranslationModelName();

            $fillableFields = (new $class)->getFillable();
            $inputData = collect(request()->input())->only($fillableFields)->toArray();

            // Debug logging
            Log::info('=== SAVE TRANSLATION DEBUG ===');
            Log::info('Class: ' . $class);
            Log::info('Origin ID: ' . $this->getKey());
            Log::info('Locale: ' . ($locale ?: app()->getLocale()));
            Log::info('Fillable fields: ' . json_encode($fillableFields));
            Log::info('Input data after filter: ' . json_encode($inputData));

            // Use updateOrCreate to avoid duplicate entry errors
            $translation = $class::updateOrCreate(
                [
                    'origin_id' => $this->getKey(),
                    'locale' => $locale ?: app()->getLocale(),
                ],
                $inputData
            );

            Log::info('Translation saved with ID: ' . $translation->id);

            if($saveSEO){
                $translation->saveSEO(request(),$locale);
            }
        }
        return true;
    }

    /**
     * Get Translated Model
     *
     * @param false $locale
     * @return $this
     */
    public function translate($locale = false){
        if(!is_enable_multi_lang()){
            return $this;
        }
        if(empty($locale)) $locale = app()->getLocale();

        $class = $this->getTranslationModelName();

        if($locale == app()->getLocale()){
            $find = $this->translation;
        }else {
            $find = $class::query()->where([
                'origin_id' => $this->getKey(),
                'locale' => $locale,
            ])->first();
        }
        if(!$find){
            $find = app()->make($class);
            $find->locale = $locale;
            $find->origin_id = $this->getKey();

            // Cant use fill() here cuz it wont work with cast keys
            // Use getFillable() method instead of accessing protected $fillable property directly
            $fillableKeys = $find->getFillable() ?? [];
            foreach ($fillableKeys as $key) {
                $find->setAttribute($key,$this->getAttribute($key));
            }
        }

        return $find;
    }


    /**
     * @internal will change to private
     */
    public function getTranslationModelName(): string
    {
        $class = $this->translation_class;

        if(!$class and class_exists(get_class($this).'Translation')){
            $class = get_class($this).'Translation';
        }
        return $class;
    }

    public function translation(){
        return $this->hasOne($this->getTranslationModelName(),'origin_id')->where('locale',app()->getLocale());
    }

    /**
     * @todo Save Translation or Origin Language
     * @param bool $locale
     * @param bool $saveSeo
     * @return bool|null
     */
    public function saveOriginOrTranslation($locale = false,$saveSeo = true)
    {
        if(!$locale) $locale = get_main_lang();

        if(is_default_lang($locale)){
            // Main lang, we need to save origin table also
            $this->save();
        }

        if($saveSeo){
            $this->saveSEO(request(),is_default_lang($locale) ? null : $locale );
        }

        $res = $this->saveTranslation($locale,$saveSeo);
        return $res;
    }

    /**
     * Force save translation row regardless of `site_enable_multi_lang` setting.
     * Use when you want translation features to work without depending on the global gate.
     */
    public function forceSaveTranslation($locale, array $data = null)
    {
        $class = $this->getTranslationModelName();
        $fillable = (new $class)->getFillable();
        $payload = $data !== null
            ? collect($data)->only($fillable)->toArray()
            : collect(request()->input())->only($fillable)->toArray();

        return $class::updateOrCreate(
            [
                'origin_id' => $this->getKey(),
                'locale' => $locale,
            ],
            $payload
        );
    }

    /**
     * Force fetch translation row regardless of `site_enable_multi_lang` setting.
     * Returns the translation model if found, otherwise null.
     */
    public function forceTranslate($locale)
    {
        if (empty($locale)) return null;
        $class = $this->getTranslationModelName();
        return $class::query()->where([
            'origin_id' => $this->getKey(),
            'locale' => $locale,
        ])->first();
    }

}
