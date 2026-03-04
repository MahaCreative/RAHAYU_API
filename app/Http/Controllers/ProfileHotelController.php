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
        if ($profileHotel) {
            $profileHotel->fasilitas_list = $profileHotel->fasilitas
                ? array_values(array_filter(array_map('trim', explode(',', $profileHotel->fasilitas))))
                : [];
            $profileHotel->foto_lainnya_list = $profileHotel->foto_lainnya
                ? array_values(array_filter(array_map('trim', explode(',', $profileHotel->foto_lainnya))))
                : [];
        }
        return response()->json($profileHotel);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $profileHotel = ProfileHotel::firstOrFail();
        $validated = $request->validate([
            "nama_hotel" => 'required|string|min:3|max:50',
            "subtitle" => 'nullable|string|min:3|max:100',
            "alamat_hotel" => 'required|string|min:10|max:100',
            "nomor_telepon" => 'required|numeric|digits_between:10,15',
            "email_hotel" => 'required|email',
            "deskripsi_hotel" => 'nullable|string|min:3|max:500',
            "logo_hotel" => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            "foto_hotel" => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            "fasilitas" => 'required',
            "kebijakan_hotel" => 'nullable|string|min:3|max:500',
            "jam_check_in" => 'nullable|string|max:20',
            "jam_check_out" => 'nullable|string|max:20',
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
        $fotoLainnya = [];
        if (!empty($profileHotel->foto_lainnya)) {
            $fotoLainnya = array_values(array_filter(array_map('trim', explode(',', $profileHotel->foto_lainnya))));
        }

        foreach ($request->file('foto_lainnya', []) as $file) {
            $fotoLainnya[] = $file->store('profile_hotel', 'public');
        }

        $fasilitasValue = $validated['fasilitas'];
        if (is_array($fasilitasValue)) {
            $fasilitasValue = implode(',', $fasilitasValue);
        }

        $profileHotel->update([
            "nama_hotel" => $validated['nama_hotel'],
            "subtitle" => $validated['subtitle'] ?? null,
            "alamat_hotel" => $validated['alamat_hotel'],
            "nomor_telepon" => $validated['nomor_telepon'],
            "email_hotel" => $validated['email_hotel'],
            "deskripsi_hotel" => $validated['deskripsi_hotel'] ?? null,
            "logo_hotel" => $logo_hotel,
            "foto_hotel" => $foto_hotel,
            "fasilitas" => $fasilitasValue,
            "kebijakan_hotel" => $validated['kebijakan_hotel'] ?? null,
            "jam_check_in" => $validated['jam_check_in'] ?? null,
            "jam_check_out" => $validated['jam_check_out'] ?? null,
            "foto_lainnya" => !empty($fotoLainnya) ? implode(',', $fotoLainnya) : null,
        ]);

        return response()->json($profileHotel);
    }
}
