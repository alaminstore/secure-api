<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|String|min:2|max:100',
            'last_name' => 'required|String|min:2|max:100',
            'username' => 'required|unique:users|min:2|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'mgs' => 'Account Created Successfully',
            'status' => 200,
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['status' => 401, 'mgs' => 'Username or Password is incorrect']);
        }

        return $this->responseWithToken($token);
    }

    protected function responseWithToken($token)
    {
        return response()->json([
            'statur' => 200,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function logout()
    {
        try {
            auth()->logout();
            return response()->json(['status' => 200, 'mgs' => 'User logged out!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'mgs' => $e->getMessage()]);
        }
    }

    public function refreshToken()
    {
        if (auth()->user()) {
            return $this->responseWithToken(auth()->refresh());
        } else {
            return response()->json(['status' => 401, 'mgs' => 'User is not Authenticated!']);
        }
    }

    public function profile()
    {
        try {
            return response()->json(['status' => 200, 'data' => auth()->user()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 400, 'mgs' => $e->getMessage()]);
        }
    }

    public function updateProfile(Request $request)
    {
        if (auth()->user()) {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $user = User::find($request->id);
            $user->update($request->all()); //or we can update menually like $user->first_name = $request->first_name; (if only for particulars columns value update)
            return response()->json(['status' => 200, 'mgs' => 'User information updated Successfully!', 'date' => $user]);
        } else {
            return response()->json(['status' => 401, 'mgs' => 'User is not Authenticated!']);
        }
    }
}
