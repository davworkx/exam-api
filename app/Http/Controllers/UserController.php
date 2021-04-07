<?php

namespace App\Http\Controllers;

use App\Mail\SendInvites;
use App\Mail\SendOtp;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Notifications\SignupOtpNotification;
use Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __invoke()
    {
        // ...
    }

    public function testMail()
    {
        //Mail::to('lhestdave@outlook.com')->send('testststs');
        // return $request;
        $email = 'lhestdave@outlook.com';
        // return $email;
        $data = [
                'subject' => 'subject',
                'message' => 'test msg',
            ];

        Mail::to($email)->queue(new SendOtp($data));

        return "Sent";
    }

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_name' => 'required|string|min:4|max:20|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_role' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        try{

            $user = new User;
            $user->name = $request->name;
            $user->user_name = $request->user_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->user_role = $request->user_role;
            $user->registered_at = now();
            $user->save();
            
            //generare OTP
            $otpCode = $this->generateOTP();
            $otp = new Otp;
            $otp->email = $request->email;
            $otp->code = $otpCode;
            $otp->save();


            //send notification
            $email = $request->email;
            // return $email;
            $data = [
                    'subject' => 'subject',
                    'message' => 'test msg',
                    'otp' => $otpCode
                ];

            Mail::to($email)->queue(new SendOtp($data));

            return response()->json([
                'status' => 'success',
                'status_code' => 201,
                'message' => 'We have sent you an email to verify your account.',
                'data' => [ 'email' => $user->email, 'name' => $user->name]
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'There was a problem on the Server.'.$e
            ], 500);
        }

    }

    public function generateOTP(){
        $otp = mt_rand(100000,999999);
        return $otp;
    }

    public function verifyAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|integer',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        try{

            $otp = Otp::where(['email' => $request->email])->first();

            if (! $otp || ! ($request->code == $otp->code)) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 404,
                    'message' => 'OTP has expired or invalid.'
                ], 404);
            }
            if (Carbon::parse($otp->created_at)->addMinutes(5)->isPast()) {
                Otp::where('email', $request->email)->delete();
                return response()->json([
                    'status' => 'error',
                    'status_code' => 404,
                    'message' => 'OTP has expired.'
                ], 404);
            }

            Otp::where('email', $request->email)->delete();
            User::where('email', $request->email)->update(['email_verified_at' => now() ]);
            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status'=>'success',
                'status_code' => 200,
                'message' => 'Account verified, please log in your account.',
                'email' => $user->email
            ], 200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'There was a problem on the Server.'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_name' => 'required|string|min:4|max:20',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'user_role' => 'required|string',
            'avatar' => ['required','image', 'mimes:jpeg,png,jpg,gif,svg', Rule::dimensions()->maxWidth(256)->maxHeight(256)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

       

        try{

            $authuser =  $request->user(); 

            $avatar = time().'.'.$request->avatar->extension();
            $request->avatar->move(public_path('uploads'), $avatar); 

            //$avatar  =  Image::create(["image_name" => $avatar]);

            $user = User::find($authuser->id);
            $user->name = $request->name;
            $user->user_name = $request->user_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->user_role = $request->user_role;
            $user->avatar = $avatar;
            $user->save();

            return response()->json([
                'status'=>'success',
                'status_code' => 200,
                'message' => 'Profile has been updated.',
            ], 200);
    
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'There was a problem on the Server.'
            ], 500);
        }
    }

    public function sendInvites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if($user->user_role != 'admin')
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => 'You are not allowed to send invites.'
            ], 422);
        //send notification
        $email = $request->email;
        // return $email;
        $data = [
                'subject' => 'Invitation',
                'message' => 'test msg',
            ];

        Mail::to($email)->queue(new SendInvites($data));

        return response()->json([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'The invitation was sent to '.$request->email
        ], 200);

    }
}
