<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AuthUserController extends Controller
{
    //
    public function login(Request $request)
    {
        

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'status_code' => 422,
                'message' => $validator->errors()
            ], 422);
        }

        try{
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'status_code' => 404,
                    'message' => 'Invalid email or password.'
                ], 404);
            }
        
            return $user->createToken($request->device_name)->plainTextToken;

        }catch( Exception $e){
            return response()->json([
                'status' => 'error',
                'status_code' => 500,
                'message' => 'There was a problem on the Server.'
            ], 500);
        }
    }
}
