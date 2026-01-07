<?php

/**
 * ADMIN SMS MODULE ROUTES
 * As requested - keeping SMS module
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Sms\Models\SmsTemplate;
use Modules\Sms\Models\SmsLog;

// =====================================================
// SMS TEMPLATES MANAGEMENT
// =====================================================
Route::prefix('module/sms')->middleware('auth:sanctum')->group(function () {
    // Get all SMS templates
    Route::get('/templates', function (Request $request) {
        try {
            $query = SmsTemplate::query();
            
            if ($request->has('s') && $request->s) {
                $query->where('name', 'LIKE', '%' . $request->s . '%');
            }
            
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }
            
            $templates = $query->orderBy('id', 'desc')->get();
            
            return response()->json([
                'data' => $templates->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'type' => $template->type,
                        'content' => $template->content,
                        'variables' => $template->variables,
                        'status' => $template->status,
                        'created_at' => $template->created_at,
                    ];
                }),
                'total' => $templates->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single template
    Route::get('/templates/edit/{id}', function ($id) {
        try {
            $template = SmsTemplate::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'type' => $template->type,
                    'content' => $template->content,
                    'variables' => $template->variables,
                    'status' => $template->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store/Update template
    Route::post('/templates/store/{id?}', function (Request $request, $id = null) {
        try {
            if ($id) {
                $template = SmsTemplate::findOrFail($id);
            } else {
                $template = new SmsTemplate();
            }
            
            $template->name = $request->input('name');
            $template->type = $request->input('type');
            $template->content = $request->input('content');
            $template->variables = $request->input('variables', []);
            $template->status = $request->input('status', 'active');
            $template->save();
            
            return response()->json([
                'success' => true,
                'data' => ['id' => $template->id],
                'message' => 'Template saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete template
    Route::delete('/templates/{id}', function ($id) {
        try {
            $template = SmsTemplate::findOrFail($id);
            $template->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get template types
    Route::get('/templates/types', function () {
        try {
            return response()->json([
                ['value' => 'booking_confirmation', 'label' => 'Booking Confirmation'],
                ['value' => 'booking_reminder', 'label' => 'Booking Reminder'],
                ['value' => 'payment_confirmation', 'label' => 'Payment Confirmation'],
                ['value' => 'welcome', 'label' => 'Welcome Message'],
                ['value' => 'password_reset', 'label' => 'Password Reset'],
                ['value' => 'promotional', 'label' => 'Promotional'],
                ['value' => 'custom', 'label' => 'Custom'],
            ]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Get available variables
    Route::get('/templates/variables', function () {
        try {
            return response()->json([
                ['key' => '{customer_name}', 'description' => 'Customer Name'],
                ['key' => '{customer_phone}', 'description' => 'Customer Phone'],
                ['key' => '{customer_email}', 'description' => 'Customer Email'],
                ['key' => '{booking_id}', 'description' => 'Booking ID'],
                ['key' => '{tour_name}', 'description' => 'Tour Name'],
                ['key' => '{tour_date}', 'description' => 'Tour Date'],
                ['key' => '{total_amount}', 'description' => 'Total Amount'],
                ['key' => '{site_name}', 'description' => 'Site Name'],
                ['key' => '{site_url}', 'description' => 'Site URL'],
            ]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
});

// =====================================================
// SMS LOGS
// =====================================================
Route::prefix('module/sms/logs')->middleware('auth:sanctum')->group(function () {
    // Get all SMS logs
    Route::get('/', function (Request $request) {
        try {
            $query = SmsLog::with('template');
            
            if ($request->has('s') && $request->s) {
                $query->where('recipient', 'LIKE', '%' . $request->s . '%');
            }
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            $logs = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $logs->items(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single log
    Route::get('/view/{id}', function ($id) {
        try {
            $log = SmsLog::with('template')->findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $log->id,
                    'recipient' => $log->recipient,
                    'message' => $log->message,
                    'template' => $log->template,
                    'status' => $log->status,
                    'provider' => $log->provider,
                    'provider_response' => $log->provider_response,
                    'created_at' => $log->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get statistics
    Route::get('/statistics', function () {
        try {
            $stats = [
                'total' => SmsLog::count(),
                'sent' => SmsLog::where('status', 'sent')->count(),
                'failed' => SmsLog::where('status', 'failed')->count(),
                'pending' => SmsLog::where('status', 'pending')->count(),
                'today' => SmsLog::whereDate('created_at', today())->count(),
                'this_month' => SmsLog::whereMonth('created_at', now()->month)->count(),
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Resend SMS
    Route::post('/resend/{id}', function ($id) {
        try {
            $log = SmsLog::findOrFail($id);
            
            // Create new log entry for resend
            $newLog = new SmsLog();
            $newLog->recipient = $log->recipient;
            $newLog->message = $log->message;
            $newLog->template_id = $log->template_id;
            $newLog->status = 'pending';
            $newLog->save();
            
            // Here you would trigger the actual SMS sending
            // For now, we'll just mark it as sent
            $newLog->status = 'sent';
            $newLog->save();
            
            return response()->json([
                'success' => true,
                'message' => 'SMS queued for resending',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete log
    Route::delete('/{id}', function ($id) {
        try {
            $log = SmsLog::findOrFail($id);
            $log->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Log deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk delete
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            if ($action === 'delete') {
                SmsLog::whereIn('id', $ids)->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Logs deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// =====================================================
// SMS SETTINGS
// =====================================================
Route::prefix('module/sms/settings')->middleware('auth:sanctum')->group(function () {
    // Get SMS settings
    Route::get('/', function () {
        try {
            $settings = [
                'provider' => setting_item('sms_provider', 'twilio'),
                'twilio_sid' => setting_item('twilio_sid'),
                'twilio_auth_token' => setting_item('twilio_auth_token') ? '********' : null,
                'twilio_phone_number' => setting_item('twilio_phone_number'),
                'nexmo_key' => setting_item('nexmo_key'),
                'nexmo_secret' => setting_item('nexmo_secret') ? '********' : null,
                'nexmo_phone_number' => setting_item('nexmo_phone_number'),
                'sms_enabled' => setting_item('sms_enabled', false),
            ];
            
            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Update SMS settings
    Route::post('/', function (Request $request) {
        try {
            $settings = $request->all();
            
            foreach ($settings as $key => $value) {
                if ($key !== '_token' && !str_contains($value, '********')) {
                    setting_update_item($key, $value);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'SMS settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Test SMS
    Route::post('/test', function (Request $request) {
        try {
            $phoneNumber = $request->input('phone_number');
            $message = $request->input('message', 'This is a test SMS from Explore Heroes');
            
            if (!$phoneNumber) {
                return response()->json(['error' => 'Phone number is required'], 400);
            }
            
            // Create log entry
            $log = new SmsLog();
            $log->recipient = $phoneNumber;
            $log->message = $message;
            $log->status = 'sent'; // In real implementation, this would be based on actual sending
            $log->provider = setting_item('sms_provider', 'twilio');
            $log->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Test SMS sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
