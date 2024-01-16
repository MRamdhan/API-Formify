<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    function login(Request $request) {        
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required|min:5'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Invalid field',
                'errors' => $validator->errors()
            ], 422);
        }

        $credential = $request->only(['email', 'password']);
        if(!Auth::attempt($credential)){
            return response()->json([
                'message' => 'Email or password incorrect'
            ], 401);
        }

        $user = User::where('id', auth()->id())->first();
        $user->accessToken = $user->createToken('authToken')->plainTextToken;
        $user->makeHidden("id");

        return response()->json([
            'message' => 'Login success',
            'user' => $user
        ], 200);
    }

    function logout(Request $request) {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout success',
        ], 200);
    }
}
