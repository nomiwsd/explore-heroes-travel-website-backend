<?php
namespace Modules\Page\Models;

use App\BaseModel;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\SEO;

class Page extends BaseModel
{
    use SoftDeletes;

    protected $table = 'core_pages';
    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'short_desc',
        'image_id',
        'header_style',
        'custom_logo',
        'banner_title',
        'banner_image_id',
        'display_order',
        'show_in_menu',
        'show_in_header',
        'show_in_footer',
        'meta_title',
        'meta_desc',
        'meta_keywords',
        'is_homepage',
        'template_id'
    ];
    protected $slugField     = 'slug';
    protected $slugFromField = 'title';
    protected $cleanFields = [
        'content',
    ];

    public $translatedAttributes = [
        'title',
        'content',
        'short_desc',
    ];

    protected $seo_type = 'page';

    protected $sitemap_type = 'page';

    protected $casts = [
        'show_in_menu' => 'boolean',
        'show_in_header' => 'boolean',
        'show_in_footer' => 'boolean',
        'is_homepage' => 'boolean',
        'display_order' => 'integer',
    ];

    // Query Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeMenuPages($query)
    {
        return $query->where('show_in_menu', true)->orderBy('display_order', 'asc');
    }

    public function scopeHeaderPages($query)
    {
        return $query->where('show_in_header', true)->orderBy('display_order', 'asc');
    }

    public function scopeFooterPages($query)
    {
        return $query->where('show_in_footer', true)->orderBy('display_order', 'asc');
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    public function getDetailUrl($locale = false)
    {
        return route('page.detail',['slug'=>$this->slug]);
    }

    public static function getModelName()
    {
        return __("Page");
    }

    public static function getAsMenuItem($id)
    {
        return parent::select('id', 'title as name')->find($id);
    }

    public static function searchForMenu($q = false)
    {
        $query = static::select('id', 'title as name');
        if (strlen($q)) {

            $query->where('title', 'like', "%" . $q . "%");
        }
        $a = $query->orderBy('id', 'desc')->limit(10)->get();
        return $a;
    }

    public function getEditUrlAttribute()
    {
        return url(route('page.admin.edit',['id'=>$this->id]));
    }

    public function banner_image()
    {
        return $this->belongsTo(\Modules\Media\Models\MediaFile::class, 'banner_image_id');
    }

    public function image()
    {
        return $this->belongsTo(\Modules\Media\Models\MediaFile::class, 'image_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\User::class, 'create_user', 'id')->withDefault();
    }
}
