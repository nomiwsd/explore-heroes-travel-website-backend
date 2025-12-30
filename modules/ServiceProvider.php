<?php
namespace Modules;

// Removed Theme dependency for backend-only setup
// use Modules\Theme\ModuleProvider;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected static $coreModuleNames = ['Api','Social','Sms','News','Admin','Booking','Order','Contact','Core','Email','Language','Media','Report','Review','User','Vendor'];

    protected static $installedModules = [];

    public function boot()
    {
        $listModule = array_map('basename', \Illuminate\Support\Facades\File::directories(__DIR__));
        foreach ($listModule as $module) {
            if (is_dir(__DIR__ . '/' . $module . '/Views')) {
                $this->loadViewsFrom(__DIR__ . '/' . $module . '/Views', $module);
            }
        }
    }

    public function register()
    {
        // Register all module providers
        foreach (static::getInstalledModules() as $module => $class) {
            if (class_exists($class)) {
                $this->app->register($class);
            }
        }
    }


    public static function getModules(){
        return array_keys(static::getActivatedModules());
    }

    public static function getActivatedModules(){
        $res = [];
        
        // Backend-only: Return installed modules directly
        foreach (static::getInstalledModules() as $module => $class) {
            if(class_exists($class)) {
                $res[$module] = [
                    'id'=>$module,
                    'class'=>$class,
                    'parent'=>''
                ];
            }
        }

        return $res;
    }
    
    public static function getCoreModules(){
        $res = [];
        
        // Backend-only: Return installed modules
        foreach (static::getInstalledModules() as $module => $class) {
            if(class_exists($class)) {
                $res[$module] = [
                    'id'=>$module,
                    'class'=>$class
                ];
            }
        }

        return $res;
    }
    
    public static function getThemeModules(){
        // Backend-only: No theme modules
        return [];
    }

    public static function getInstalledModules(){

        if(empty(static::$installedModules)){
            $listModule = array_map('basename', \Illuminate\Support\Facades\File::directories(__DIR__));
            foreach ($listModule as $module) {
                if (is_dir(__DIR__ . '/' . $module . '/Views')) {
                    static::$installedModules[$module] = "\\Modules\\".ucfirst($module)."\\ModuleProvider";
                }
            }
        }
        return static::$installedModules;
    }
    public static function getManageableModules(){
        $res = [];
        foreach (static::getInstalledModules() as $id=>$class){
            if(!in_array($id,static::$coreModuleNames)) $res[$id] = $class;
        }
        return $res;
    }
}
