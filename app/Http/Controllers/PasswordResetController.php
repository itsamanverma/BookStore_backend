<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\PasswordResetRequest;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'bail|required|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => "We can't find a user with that email address."], 200);
            // return response()->json(['error' => $validator->errors()], 200);
        }
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60)
            ]
        );
        if ($user && $passwordReset) {
            $user->notify(new PasswordResetRequest($passwordReset->token));
        }
        return response()->json(['message' => 'We have emailed your password reset link!'], 201);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find()
    {
        $token = request('token');
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset)
            return response()->json([
            'message' => 'This password reset token is invalid.'
        ], 200);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 200);
        }
        return response()->json(['message' => $passwordReset], 201);
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:8|max:15',
            'rpassword' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'password doesnt match'], 201);
        }
        $passwordReset = PasswordReset::where([
            ['token', $request->token]
        ])->first();
        if (!$passwordReset)
            return response()->json([
            'message' => 'This password reset token is invalid.'
        ], 200);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
            'message' => "We can't find a user with that e-mail address."
        ], 200);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
     //   $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json([ 'message' => "Password Successfully Reset"],201);
    }


}
 