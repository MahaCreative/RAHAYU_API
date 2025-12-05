<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->where('profile_completed', 'yes');
        if ($request->cari) {
            $query->where('name', 'like', '%' . $request->cari . '%');
        }
        if ($request->jenis_kelamin) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $user = $query->get();
        return response()->json([
            'data' => $user
        ]);
    }

    public function blockUser(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        $user->status = 'non active';
        $user->save();
        return response()->json([
            'message' => 'User blocked successfully'
        ]);
    }
    public function unblockUser(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        $user->status = 'active';
        $user->save();
        return response()->json([
            'message' => 'User blocked successfully'
        ]);
    }

    public function show(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        return response()->json([
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
