<?php

/**
 * ADMIN CONTACT MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Contact\Models\Contact;

// =====================================================
// CONTACT SUBMISSION MANAGEMENT
// =====================================================
Route::prefix('module/contact')->middleware('auth:sanctum')->group(function () {
    // Get all contact submissions
    Route::get('/', function (Request $request) {
        try {
            $query = Contact::query();
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->s . '%')
                      ->orWhere('email', 'LIKE', '%' . $request->s . '%')
                      ->orWhere('subject', 'LIKE', '%' . $request->s . '%');
                });
            }
            
            // Status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Type filter
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }
            
            // Date range filter
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            $contacts = $query->orderBy('id', 'desc')->paginate($request->input('limit', 20));
            
            return response()->json([
                'data' => $contacts->items(),
                'total' => $contacts->total(),
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single contact submission
    Route::get('/view/{id}', function ($id) {
        try {
            $contact = Contact::findOrFail($id);
            
            // Mark as read
            if ($contact->status === 'new') {
                $contact->status = 'read';
                $contact->save();
            }
            
            return response()->json([
                'data' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'subject' => $contact->subject,
                    'message' => $contact->message,
                    'type' => $contact->type,
                    'status' => $contact->status,
                    'notes' => $contact->notes,
                    'ip_address' => $contact->ip_address,
                    'user_agent' => $contact->user_agent,
                    'created_at' => $contact->created_at,
                    'updated_at' => $contact->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update contact (add notes, change status)
    Route::post('/update/{id}', function (Request $request, $id) {
        try {
            $contact = Contact::findOrFail($id);
            
            $contact->status = $request->input('status', $contact->status);
            $contact->notes = $request->input('notes', $contact->notes);
            
            $contact->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Mark as read
    Route::post('/markRead/{id}', function ($id) {
        try {
            $contact = Contact::findOrFail($id);
            $contact->status = 'read';
            $contact->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Mark as replied
    Route::post('/markReplied/{id}', function ($id) {
        try {
            $contact = Contact::findOrFail($id);
            $contact->status = 'replied';
            $contact->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Marked as replied',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete contact
    Route::delete('/{id}', function ($id) {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk edit
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            switch ($action) {
                case 'delete':
                    Contact::whereIn('id', $ids)->delete();
                    break;
                case 'read':
                    Contact::whereIn('id', $ids)->update(['status' => 'read']);
                    break;
                case 'replied':
                    Contact::whereIn('id', $ids)->update(['status' => 'replied']);
                    break;
                case 'archived':
                    Contact::whereIn('id', $ids)->update(['status' => 'archived']);
                    break;
                default:
                    return response()->json(['error' => 'Invalid action'], 400);
            }
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($action) . ' completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get statistics
    Route::get('/statistics', function () {
        try {
            $stats = [
                'total' => Contact::count(),
                'new' => Contact::where('status', 'new')->count(),
                'read' => Contact::where('status', 'read')->count(),
                'replied' => Contact::where('status', 'replied')->count(),
                'archived' => Contact::where('status', 'archived')->count(),
                'today' => Contact::whereDate('created_at', today())->count(),
                'this_week' => Contact::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => Contact::whereMonth('created_at', now()->month)->count(),
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
    
    // Export contacts
    Route::get('/export', function (Request $request) {
        try {
            $query = Contact::query();
            
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            $contacts = $query->orderBy('id', 'desc')->get(['id', 'name', 'email', 'phone', 'subject', 'message', 'status', 'created_at']);
            
            return response()->json([
                'data' => $contacts,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});
