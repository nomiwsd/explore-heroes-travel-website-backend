<?php
namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Models\Subscriber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'source_page' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()], 200);
        }

        $email = $request->input('email');
        
        $row = Subscriber::where('email', $email)->first();
        if ($row) {
            return response()->json([
                'error'    => true,
                'messages' => [
                   'email' => [__('Email already exists')]
                ]
            ], 200);
        }

        $row = new Subscriber();
        $row->fill($request->input());
        $row->status = 'publish';
        
        if ($row->save()) {
            return response()->json([
                'error'   => false,
                'message' => __('Thank you for subscribing!'),
            ], 200);
        }

        return response()->json([
            'error'   => true,
            'message' => __('Something went wrong! Please try again.')
        ], 200);
    }

    public function index(Request $request)
    {
        /** @var \App\User $user */
        $user = Auth::user();
        if (!$user || !$user->hasPermission('newsletter_manage')) {
            return response()->json(['error' => true, 'message' => __('You do not have permission to access this resource')], 403);
        }

        $query = Subscriber::query();

        if ($s = $request->input('s')) {
            $query->where('email', 'LIKE', '%' . $s . '%')
                  ->orWhere('first_name', 'LIKE', '%' . $s . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $s . '%');
        }

        $rows = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'data' => $rows->items(),
            'total' => $rows->total(),
            'per_page' => $rows->perPage(),
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
        ]);
    }
}
