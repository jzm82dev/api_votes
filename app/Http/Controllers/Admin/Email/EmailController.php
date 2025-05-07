<?php

namespace App\Http\Controllers\Admin\Email;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Mail\SendEmail;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    

    public function sendEmailMarketing( Request $request){

        switch ($request->email_type) {
            case 'email_1':
                $resp = Mail::to($request->email)->send( new SendEmail('marketing_email_1_message', 'Ahorra más de 1.000€ anuales'));
                break;

            case 'email_2':
                $resp = Mail::to($request->email)->send( new SendEmail('marketing_email_1_message', 'Ahorra más de 1.000€ anuales'));
                break;

            case 'email_3':
                $resp = Mail::to($request->email)->send( new SendEmail('marketing_email_3', 'Ahorra más de 1.000€ anuales'));
                break;

            default:
                # code...
                break;
        }



        return response()->json([
            'message' => 200,
            'message_text' => 'Club saved correctly',
            'email_response' => $resp
        ]);

    }

}
