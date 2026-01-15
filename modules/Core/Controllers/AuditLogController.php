<?php

namespace Modules\Core\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\User;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = AuditLog::with('user');
            
            if ($request->has('user_id') && $request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            
            if ($request->has('event') && $request->event) {
                $query->where('event', $request->event);
            }
            
            if ($request->has('auditable_type') && $request->auditable_type) {
                $query->where('auditable_type', 'LIKE', '%' . $request->auditable_type . '%');
            }

            if ($request->has('s') && $request->s) {
                 // Search in old_values or new_values json? expensive.
                 // Maybe search by ip_address or user name via relation?
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $logs->map(function($log) {
                    return [
                        'id' => $log->id,
                        'user_id' => $log->user_id,
                        'user_name' => $log->user ? $log->user->getDisplayName() : 'System',
                        'user_avatar' => $log->user ? $log->user->getAvatarUrl() : null, // Assuming getAvatarUrl exists or similar
                        'event' => $log->event,
                        'auditable_type' => class_basename($log->auditable_type),
                        'auditable_id' => $log->auditable_id,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at,
                        'new_values' => $log->new_values,
                        'old_values' => $log->old_values,
                    ];
                }),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
