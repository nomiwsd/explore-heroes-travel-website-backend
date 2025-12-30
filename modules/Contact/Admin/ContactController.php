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

        $s = $request->query('s');
        $datapage = New Contact;
        if ($s) {
            $datapage->where(function ($query) use ($s){
                $query->where('name', 'LIKE', '%' . $s . '%')
                    ->orWhere('email','LIKE', '%' . $s . '%')
                    ->orWhere('message','LIKE', '%' . $s . '%')
                ;
            });
        }

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($datapage->paginate(20));
        }

        $data = [
            'rows'        => $datapage->paginate(20),
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
        if (empty($ids)) {
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('No Action is selected!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Contact::where("id", $id)->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Contact::where("id", $id);
                $query->update(['status' => $action]);
            }
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    // Export to CSV
    public function exportCsv(Request $request)
    {
        $this->checkPermission('contact_manage');

        $query = Contact::query();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('s')) {
            $s = $request->s;
            $query->where(function ($q) use ($s){
                $q->where('name', 'LIKE', '%' . $s . '%')
                    ->orWhere('email','LIKE', '%' . $s . '%')
                    ->orWhere('message','LIKE', '%' . $s . '%');
            });
        }

        $contacts = $query->get();

        $filename = 'contact_submissions_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($contacts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Message', 'Status', 'Date']);

            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->id,
                    $contact->name,
                    $contact->email,
                    $contact->phone ?? '',
                    $contact->message,
                    $contact->status ?? 'new',
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
