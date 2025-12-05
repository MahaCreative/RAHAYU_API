<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\invoice;

class PaymentController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = invoice::with(['pemesanan', 'user']);
        $perPage = (int) $request->query('per_page', 15);
        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

    public function show(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $inv = invoice::with(['pemesanan.bookingKamars.kamar', 'user'])->find($id);
        if (!$inv) return response()->json(['message' => 'Not found'], 404);
        unset($inv->petugas_id);
        if (isset($inv->petugas)) unset($inv->petugas);
        return response()->json(['data' => $inv]);
    }
}
