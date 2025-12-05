<?php

namespace App\Http\Controllers;

use App\Models\ProfileHotel;
use Illuminate\Http\Request;

class ProfileHotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profileHotel = ProfileHotel::first();
        return response()->json($profileHotel);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $profileHotel = ProfileHotel::findOrFail($id);
        $validated = $request->validate([
            "nama_hotel" => 'required|string|min:3|max:50',
            "subtitle" => 'nullable|min:25|max:100',
            "alamat_hotel" => 'required|string|min:10|max:100',
            "nomor_telepon" => 'required|numeric|digits_between:10,15',
            "email_hotel" => 'required|email',
            "deskripsi_hotel" => 'nullable|string|min:10|max:500',
            "logo_hotel" => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            "foto_hotel" => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            "fasilitas" => 'required',
            "kebijakan_hotel" => 'nullable|string|min:10|max:500',
            "jam_check_in" => 'nullable|date',
            "jam_check_out" => 'nullable|date|after:jam_check_in',
            "foto_lainnya" => 'nullable|array',
            "foto_lainnya.*" => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $logo_hotel = $profileHotel->logo_hotel;
        if ($request->hasFile('logo_hotel')) {
            $logo_hotel = $request->file('logo_hotel')->store('profile_hotel', 'public');
        }

        $foto_hotel = $profileHotel->foto_hotel;
        if ($request->hasFile('foto_hotel')) {
            $foto_hotel = $request->file('foto_hotel')->store('profile_hotel', 'public');
        }
        $validated['foto_lainnya'] = $validated['foto_lainnya'] ?? [];
        foreach ($request->file('foto_lainnya', []) as $file) {
            $validated['foto_lainnya'][] = $file->store('profile_hotel', 'public');
        }
        foreach ($request->file('foto_lainnya', []) as $file) {
            $validated['foto_lainnya'][] = $file->store('profile_hotel', 'public');
        }
        $profileHotel->update([
            "nama_hotel" => $validated['nama_hotel'],
            "subtitle" => $validated['subtitle'],
            "alamat_hotel" => $validated['alamat_hotel'],
            "nomor_telepon" => $validated['nomor_telepon'],
            "email_hotel" => $validated['email_hotel'],
            "deskripsi_hotel" => $validated['deskripsi_hotel'],
            "logo_hotel" => $logo_hotel,
            "foto_hotel" => $foto_hotel,
            "fasilitas" => $validated['fasilitas'],
            "kebijakan_hotel" => $validated['kebijakan_hotel'],
            "jam_check_in" => $validated['jam_check_in'],
            "jam_check_out" => $validated['jam_check_out'],
        ]);

        return response()->json($profileHotel);
    }
}
