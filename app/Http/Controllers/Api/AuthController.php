<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:55',
            'phone' => 'phone:AUTO,US',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed',
            'is_beneficiary' => 'bool'
        ]);

        $validatedData['password'] = bcrypt($request->password);
        $user = User::create($validatedData);
        $accessToken = $user->createToken('authToken')->accessToken;

        $stripeCustomer = $user->createOrGetStripeCustomer();

        return response(['user' => $user, 'access_token' => $accessToken, 'message' => 'Register successfully'], 200);
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($loginData)) {
            return response(['message' => 'Invalid Credentials'], 401);
        }

        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        return response(['user' => auth()->user(), 'access_token' => $accessToken, 'message' => 'Login successfully'], 200);
    }

    public function forgot_password(Request $request)
    {
        $validatedData = $request->validate([
            'email' => "required|email"
        ]);

        try {
            $response = Password::sendResetLink($request->only('email'), function (Message $message) {
                $message->subject($this->getEmailSubject());
            });
            switch ($response) {
                case Password::RESET_LINK_SENT:
                    return response(['message' => trans($response)], 200);

                case Password::INVALID_USER:
                    return response(['message' => trans($response)], 400);
            }
        } catch (\Throwable $ex) {
            return response(['message' => $ex->getMessage()], 400);
        }
    }

    public function change_password(Request $request)
    {
        $currentUser = auth()->user();
        $userid = auth()->user()->id;

        $validatedData = $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password'
        ]);

        try {
            if ((Hash::check(request('old_password'), $currentUser->password)) == false) {
                return response(['message' => "Check your old password."], 400);
            } else if ((Hash::check(request('new_password'), $currentUser->password)) == true) {
                return response(['message' => "Please enter a password which is not similar then current password."], 400);
            } else {
                User::where('id', $userid)->update(['password' => Hash::make($request->new_password)]);
                return response(['message' => "Password updated successfully."], 200);
            }
        } catch (\Exception $ex) {
            if (isset($ex->errorInfo[2])) {
                $msg = $ex->errorInfo[2];
            } else {
                $msg = $ex->getMessage();
            }
            return response(['message' => $msg], 400);
        }
    }
}
