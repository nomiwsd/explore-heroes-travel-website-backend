<?php

namespace Modules\Core\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\AuditLog;

class AuditLogObserver
{
    public function created(Model $model)
    {
        $this->log($model, 'create');
    }

    public function updated(Model $model)
    {
        // Ignore if only 'updated_at' changed
        if ($model->wasChanged() && count($model->getChanges()) === 1 && array_key_exists('updated_at', $model->getChanges())) {
            return;
        }
        
        $this->log($model, 'update');
    }

    public function deleted(Model $model)
    {
        $this->log($model, 'delete');
    }
    
    public function restored(Model $model)
    {
        $this->log($model, 'restore');
    }
    
    public function forceDeleted(Model $model)
    {
        $this->log($model, 'force_delete');
    }
    
    protected function log(Model $model, $action)
    {
        if (!Auth::check()) return;

        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => $action,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'old_values' => $action === 'update' ? json_encode($model->getOriginal()) : null,
                'new_values' => $action !== 'delete' ? json_encode($model->getAttributes()) : null,
                'url' => request()->fullUrl(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // content
        }
    }
}
