<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 6/5/2019
 * Time: 11:31 AM
 */
namespace Modules\Contact\Admin;

use Illuminate\Support\Facades\Route;
use function Clue\StreamFilter\fun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Contact\Models\Contact;

class ContactController extends AdminController
{
    public function __construct()
    {
        if(Route::has('report.admin.booking'))
        $this->setActiveMenu(route('report.admin.booking'));
    }

    public function index(Request $request)
    {
        $this->checkPermission('contact_manage');

        $query = Contact::query();
        
        $s = $request->query('s') ?? $request->query('search');
        if ($s) {
            $query->where(function ($q) use ($s){
                $q->where('name', 'LIKE', '%' . $s . '%')
                    ->orWhere('email','LIKE', '%' . $s . '%')
                    ->orWhere('message','LIKE', '%' . $s . '%');
            });
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by form_type
        if ($request->has('form_type') && $request->form_type !== 'all') {
            $query->where('form_type', $request->form_type);
        }
        
        $query->orderBy('created_at', 'desc');

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            $contacts = $query->with('tour:id,title')->paginate(20);
            
            // Transform to include tour_name and destination_name
            $transformed = $contacts->getCollection()->map(function ($contact) {
                $data = $contact->toArray();
                $data['tour_name'] = $contact->tour ? $contact->tour->title : null;
                // destination_name is already in the model, but ensure it's present
                $data['destination_name'] = $contact->destination_name ?? null;
                return $data;
            });
            
            return response()->json([
                'data' => $transformed,
                'total' => $contacts->total(),
                'per_page' => $contacts->perPage(),
                'current_page' => $contacts->currentPage(),
            ]);
        }

        $data = [
            'rows'        => $query->paginate(20),
            'breadcrumbs' => [
                [
                    'name' => __('Contact Submissions'),
                    'url'  => route('contact.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Contact::admin.index', $data);
    }
    
    /**
     * Get a single submission
     */
    public function show(Request $request, $id)
    {
        $this->checkPermission('contact_manage');
        
        $contact = Contact::with('tour:id,title')->findOrFail($id);
        
        // Mark as read if new
        if ($contact->status === 'new' || empty($contact->status)) {
            $contact->status = 'read';
            $contact->save();
        }
        
        $data = $contact->toArray();
        $data['tour_name'] = $contact->tour ? $contact->tour->title : null;
        $data['destination_name'] = $contact->destination_name ?? null;
        
        return response()->json($data);
    }
    
    /**
     * Update a submission (status, notes)
     */
    public function update(Request $request, $id)
    {
        $this->checkPermission('contact_manage');
        
        $contact = Contact::findOrFail($id);
        
        $validStatuses = array_keys(Contact::getStatuses());
        
        $request->validate([
            'status' => 'nullable|string|in:' . implode(',', $validStatuses),
            'notes' => 'nullable|string|max:5000',
        ]);
        
        if ($request->has('status')) {
            $contact->status = $request->status;
        }
        
        if ($request->has('notes')) {
            $contact->notes = $request->notes;
        }
        
        $contact->save();
        
        $data = $contact->toArray();
        $data['tour_name'] = $contact->tour ? $contact->tour->title : null;
        $data['destination_name'] = $contact->destination_name ?? null;
        
        return response()->json([
            'status' => 1,
            'message' => 'Submission updated successfully',
            'data' => $data
        ]);
    }

    public function getForSelect2(Request $request)
    {
        $q = $request->query('q');
        $query = Contact::select('id', 'title as text');
        if ($q) {
            $query->where('title', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('contact_manage');

        $ids = $request->input('ids');
        $action = $request->input('action');
        $status = $request->input('status');
        
        if (empty($ids)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['status' => 0, 'message' => 'Please select at least 1 item!'], 400);
            }
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['status' => 0, 'message' => 'No Action is selected!'], 400);
            }
            return redirect()->back()->with('error', __('No Action is selected!'));
        }
        
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Contact::where("id", $id)->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
            $message = count($ids) . ' submission(s) deleted successfully';
        } elseif ($action == "update_status" && $status) {
            // Update to specific status
            foreach ($ids as $id) {
                $query = Contact::where("id", $id);
                $query->update(['status' => $status]);
            }
            $message = count($ids) . ' submission(s) updated to ' . $status;
        } else {
            // Action is the status itself (legacy support)
            foreach ($ids as $id) {
                $query = Contact::where("id", $id);
                $query->update(['status' => $action]);
            }
            $message = count($ids) . ' submission(s) updated successfully';
        }
        
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['status' => 1, 'message' => $message]);
        }
        
        return redirect()->back()->with('success', __('Update success!'));
    }

    // Export to CSV
    public function exportCsv(Request $request)
    {
        $this->checkPermission('contact_manage');

        $query = Contact::with('tour:id,title');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('form_type') && $request->form_type !== 'all') {
            $query->where('form_type', $request->form_type);
        }

        if ($request->has('s')) {
            $s = $request->s;
            $query->where(function ($q) use ($s){
                $q->where('name', 'LIKE', '%' . $s . '%')
                    ->orWhere('email','LIKE', '%' . $s . '%')
                    ->orWhere('message','LIKE', '%' . $s . '%');
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')->get();

        $filename = 'contact_submissions_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($contacts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Form Type', 'Subject', 'Message', 'Destination', 'Tour', 'Status', 'Notes', 'Date']);

            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->id,
                    $contact->name,
                    $contact->email,
                    $contact->phone ?? '',
                    $contact->form_type ?? 'contact',
                    $contact->subject ?? '',
                    $contact->message,
                    $contact->destination_name ?? '',
                    $contact->tour ? $contact->tour->title : '',
                    $contact->status ?? 'new',
                    $contact->notes ?? '',
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
