<?php

namespace Modules\Contact\Controllers;

use App\Helpers\ReCaptchaEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Matrix\Exception;
use Modules\Contact\Emails\NotificationToAdmin;
use Modules\Contact\Models\Contact;
use Modules\Contact\Models\TailorMadeTour;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        $data = [
            'page_title' => __("Contact Page"),
            'header_transparent' => true,
            'breadcrumbs' => [
                [
                    'name' => __('Contact'),
                    'url' => route('contact.index'),
                    'class' => 'active'
                ],
            ],
        ];
        return view('Contact::index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => [
                'required',
                'max:255',
                'email'
            ],
            'name' => ['required'],
            'phone' => ['required'],
            'message' => ['required']
        ]);
        /**
         * Google ReCapcha
         */
        if (ReCaptchaEngine::isEnable()) {
            $codeCapcha = $request->input('g-recaptcha-response');
            if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                $data = [
                    'status' => 0,
                    'message' => __('Please verify the captcha'),
                ];
                return response()->json($data, 200);
            }
        }
        $row = new Contact($request->input());
        $row->status = 'sent';
        if ($row->save()) {
            $this->sendEmail($row);
            $data = [
                'status' => 1,
                'message' => __('Thank you for contacting us! We will get back to you soon'),
            ];
            return response()->json($data, 200);
        }
    }

    public function indexTailorMadeTour(Request $request)
    {
        $data = [
            'page_title' => __("Tailor Made Tour"),
            'header_transparent' => true,
            'breadcrumbs' => [
                [
                    'name' => __('Tailor Made Tour'),
                    'url' => route('tailor-made-tour.indexTailorMadeTour'),
                    'class' => 'active'
                ],
            ],
            'countries' => config('countries')
        ];
        return view('Contact::tailor-made-tour', $data);
    }

    public function storeTailorMadeTour(Request $request)
    {
        $request->validate([
            'salutation' => ['required', 'string', 'max:10'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'country' => ['required', 'string', 'max:100'],

            // Age groups - optional, but numeric if present
            'age_13_17' => ['nullable', 'numeric', 'min:0'],
            'age_18_25' => ['nullable', 'numeric', 'min:0'],
            'age_26_35' => ['nullable', 'numeric', 'min:0'],
            'age_36_45' => ['nullable', 'numeric', 'min:0'],
            'age_46_55' => ['nullable', 'numeric', 'min:0'],
            'age_56_69' => ['nullable', 'numeric', 'min:0'],
            'age_70_above' => ['nullable', 'numeric', 'min:0'],
            'age_below_2' => ['nullable', 'numeric', 'min:0'],
            'age_3_7' => ['nullable', 'numeric', 'min:0'],
            'age_8_12' => ['nullable', 'numeric', 'min:0'],

            'interests' => ['nullable', 'array'],
            'type_of_accommodation' => ['nullable', 'string', 'max:100'],
            'budget_currency' => ['nullable', 'string', 'max:10'],
            'budget_per_person' => ['nullable', 'numeric'],

            'when_to_go' => ['nullable', 'string'],
            'trip_date' => ['nullable'],
            'no_of_nights_known' => ['nullable', 'numeric'],
            'roughly_month' => ['nullable', 'string'],
            'roughly_year' => ['nullable', 'string'],
            'no_of_nights_unknown' => ['nullable', 'numeric'],

            'long' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
        ]);
        /**
         * Google ReCapcha
         */
        if (ReCaptchaEngine::isEnable()) {
            $codeCapcha = $request->input('g-recaptcha-response');
            if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                $data = [
                    'status' => 0,
                    'message' => __('Please verify the captcha'),
                ];
                return response()->json($data, 200);
            }
        }
        $data = $request->all();

        if (isset($data['interests'])) {
            $data['interests'] = json_encode($data['interests']);
        }

        $row = new TailorMadeTour($data);

        $row->status = 'sent';
        if ($row->save()) {

            $from = env('MAIL_FROM_ADDRESS');
            $from_name = env('MAIL_FROM_NAME');
            $to = $row->email;
            $to_name = $row->first_name . ' ' . $row->last_name;
            $email_title = trans('Tailor Made Tour');
            $msg = ' <div style="padding-bottom: 30px; font-size: 17px;">
                         Hi <strong>' . $row->first_name . ' ' . $row->last_name . ',</strong>
                    </div>
                    <div style="padding-bottom: 30px">Thank you for filling tailor made tour ! We will get back to you soon.
                    </div>';
            $this->sendEmailNew($from, $from_name, $to, $to_name, $email_title, $msg);

            $from = env('MAIL_FROM_ADDRESS');
            $from_name = env('MAIL_FROM_NAME');
            $to = "hello@exploreheroes.com";
//            $to = "mohsinrao.ali@gmail.com";
            $to_name = "Explore Heroes";
            $email_title = trans('Tailor Made Tour');
            $msg = '<div style="padding-bottom: 30px; font-size: 17px;">
                         Hi <strong>Admin,</strong>
                    </div>
                    <div style="padding-bottom: 30px">You got tailor made tour enquiry below is the information.</div>
                    <ul>
                                            <li>Salutation:'.$row->salutation.'</li>
                                            <li>First Name:'.$row->first_name.'</li>
                                            <li>Last Name:'.$row->last_name.'</li>
                                            <li>Email:'.$row->email.'</li>
                                            <li>Phone:'.$row->phone.'</li>
                                            <li>Country:'.$row->country.'</li>
                                            <li>Age (13-17):'.$row->age_13_17.'</li>
                                            <li>Age (18-25):'.$row->age_18_25.'</li>
                                            <li>Age (26-35):'.$row->age_26_35.'</li>
                                            <li>Age (36-45):'.$row->age_36_45.'</li>
                                            <li>Age (46-55):'.$row->age_46_55.'</li>
                                            <li>Age (56-69):'.$row->age_56_69.'</li>
                                            <li>Age (70+):'.$row->age_70_above.'</li>
                                            <li>Age (Below 2):'.$row->age_below_2.'</li>
                                            <li>Age (3-7):'.$row->age_3_7.'</li>
                                            <li>Age (8-12):'.$row->age_8_12.'</li>
                                            <li>Interests:'. ($row->interests ? implode(', ', json_decode($row->interests)) : 'No interests specified') .'</li>
                                            <li>Type of Accommodation:'.$row->type_of_accommodation.'</li>
                                            <li>Budget Currency:'.$row->budget_currency.'</li>
                                            <li>Budget per Person:'.$row->budget_per_person.'</li>
                                            <li>When to Go:'.$row->when_to_go.'</li>
                                            <li>Trip Date:'.$row->trip_date.'</li>
                                            <li>Number of Nights (Known):'.$row->no_of_nights_known.'</li>
                                            <li>Roughly Month:'.$row->roughly_month.'</li>
                                            <li>Roughly Year:'.$row->roughly_year.'</li>
                                            <li>Number of Nights (Unknown):'.$row->no_of_nights_unknown.'</li>
                                            <li>Comments:'.$row->comments.'</li>
                                        </ul>';
            $this->sendEmailNew($from, $from_name, $to, $to_name, $email_title, $msg);


            $data = [
                'status' => 1,
                'message' => __('Thank you for filling tailor made tour ! We will get back to you soon'),
            ];
            return response()->json($data, 200);
        }
    }

    public function sendEmailNew($from, $from_name, $to, $to_name, $email_title, $msg, $dataDetail = [], $email_template = 'email-template')
    {
        try {
            Mail::send('Contact::emails.' . $email_template, [
                'title' => $email_title,
                'msg' => $msg,
                'dataDetail' => $dataDetail,
            ], function ($message) use ($to, $from, $from_name, $email_title) {
                $message->to($to)->from($from, $from_name)->subject($email_title);
            });
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }

    protected function sendEmail($contact)
    {
        if ($admin_email = setting_item('admin_email')) {
            try {
                Mail::to($admin_email)->send(new NotificationToAdmin($contact));
            } catch (Exception $exception) {
                Log::warning("Contact Send Mail: " . $exception->getMessage());
            }
        }
    }

    public function t()
    {
        return new NotificationToAdmin(Contact::find(1));
    }
}
