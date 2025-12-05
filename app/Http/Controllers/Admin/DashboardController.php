<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingKamar;
use App\Models\Tamu;
use App\Models\invoice as InvoiceModel;
use App\Models\Pemesanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    /**
     * Return dashboard summary statistics for admin
     * GET /admin/dashboard/summary
     */
    public function summary(Request $request)
    {
        $this->authorizeAdmin($request);

        // Booking status counts
        $totalBookings = BookingKamar::count();
        $pending = BookingKamar::where('status_booking', 'pending')->count();
        $confirmed = BookingKamar::where('status_booking', 'confirmed')->count();
        $checkedIn = BookingKamar::whereIn('status_booking', ['checked_in', 'in'])->count();
        $checkedOut = BookingKamar::whereIn('status_booking', ['checked_out', 'done', 'completed'])->count();
        $cancelled = BookingKamar::where(function ($q) {
            $q->where('status_booking', 'cancel')->orWhere('status_booking', 'cancelled')->orWhere('status_booking', 'rejected');
        })->count();

        // Guests (use Tamu table if populated) and bookings' jumlah_tamu fallback
        $totalTamu = Tamu::count();
        if ($totalTamu === 0) {
            $totalTamu = (int) BookingKamar::sum('jumlah_tamu');
        }

        // Users and pemesanan
        $totalUsers = User::count();
        $totalPemesanan = Pemesanan::count();

        // Invoice totals
        $totalInvoiceAmount = (float) InvoiceModel::sum('total_amount');
        $totalPaid = (float) InvoiceModel::sum('jumlah_bayar');

        // Recent metrics (last 7 days)
        $since = now()->subDays(7);
        $recentBookings = BookingKamar::whereDate('created_at', '>=', $since)->count();
        $recentGuests = Tamu::whereDate('created_at', '>=', $since)->count();
        $recentRevenue = InvoiceModel::whereDate('created_at', '>=', $since)->sum('jumlah_bayar');

        $data = [
            'bookings' => [
                'total' => $totalBookings,
                'pending' => $pending,
                'confirmed' => $confirmed,
                'checked_in' => $checkedIn,
                'checked_out' => $checkedOut,
                'cancelled' => $cancelled,
            ],
            'totals' => [
                'tamu' => $totalTamu,
                'users' => $totalUsers,
                'pemesanan' => $totalPemesanan,
                'invoice_total_amount' => $totalInvoiceAmount,
                'invoice_total_paid' => $totalPaid,
            ],
            'recent' => [
                'bookings_last_7_days' => $recentBookings,
                'guests_last_7_days' => $recentGuests,
                'revenue_last_7_days' => (float) $recentRevenue,
            ],
        ];

        return response()->json($data);
    }
}
