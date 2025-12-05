<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|unique:users,telephone',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::create([
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => bcrypt($request->password),
            'role' => 'costumer'
        ]);
        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
        $user = User::where('email', $request->email)->orWhere('telephone', $request->email)->first();
        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message' => 'User logged in successfully', 'access_token' => $token, 'token_type' => 'Bearer', 'user' => $user], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User logged out successfully'], 200);
    }

    public function forgotPassword(Request $request)
    {
        //
    }

    public function resetPassword(Request $request)
    {
        //
    }
    public function verifyOtp(Request $request)
    {
        //
    }
    public function resendOtp(Request $request)
    {
        //
    }
    public function uploadPhoto(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        if (!$request->hasFile('photo')) {
            return response()->json(['message' => 'No photo uploaded'], 400);
        }

        $file = $request->file('photo');
        $path = $file->store('uploads', 'public');

        $user->foto_profil = $path;
        $user->save();

        return response()->json(['message' => 'Photo uploaded', 'user' => $user]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $request->validate([
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'telephone' => ['nullable', 'string', Rule::unique('users', 'telephone')->ignore($user->id)],
            'password' => ['nullable', 'min:6', 'confirmed'],
        ]);

        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('telephone')) $user->telephone = $request->telephone;
        if ($request->filled('password')) $user->password = bcrypt($request->password);

        $user->save();

        return response()->json(['message' => 'Account updated', 'user' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $data = $request->only([
            'first_name',
            'last_name',
            'tanggal_lahir',
            'jenis_kelamin',
            'alamat',
            'jenis_identitas',
            'nomor_identitas'
        ]);

        // Update provided fields
        foreach ($data as $k => $v) {
            if (!is_null($v) && $v !== '') $user->{$k} = $v;
        }

        // mark profile complete
        $user->profile_complete = 'yes';
        $user->save();

        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }
}
