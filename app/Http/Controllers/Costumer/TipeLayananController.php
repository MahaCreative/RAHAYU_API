<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use App\Models\TipeLayanan;
use Illuminate\Http\Request;

class TipeLayananController extends Controller
{
    public function index(Request $request)
    {
        $query = TipeLayanan::query();
        if ($request->cari) {
            $query->where('nama_layanan', 'like', '%' . $request->cari . '%');
        }
        $page = $request->page ?? 15;
        return response()->json([
            'data' => $query->get()
        ]);
    }
    public function show(string $id, Request $request)
    {
        $query = TipeLayanan::query()->with('layanans')->where('id', $id);
        if ($request->cari) {
            $query->whereHas('layanan', function ($q) use ($request) {
                $q->where('nama_layanan', 'like', '%' . $request->cari . '%');
            });
        }
        $requestedLayanan = $query->first();
        return response()->json([
            'data' => $requestedLayanan
        ]);
    }
}
