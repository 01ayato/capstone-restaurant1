<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();

        $totalOrders = Order::query()->count();
        $totalSales = (float) Order::query()
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('total');
        $totalCustomers = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SLUG_USER))
            ->count();
        $revenueToday = (float) Order::query()
            ->where('status', Order::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->sum('total');
        $pendingOrders = Order::query()->where('status', Order::STATUS_PENDING)->count();
        $recentOrders = Order::query()->with('user')->latest()->limit(5)->get();
        $pendingReservations = Reservation::query()->where('status', Reservation::STATUS_PENDING)->count();

        return view('admin.dashboard', [
            'totalOrders' => $totalOrders,
            'totalSales' => $totalSales,
            'totalCustomers' => $totalCustomers,
            'revenueToday' => $revenueToday,
            'pendingOrders' => $pendingOrders,
            'pendingReservations' => $pendingReservations,
            'recentOrders' => $recentOrders,
        ]);
    }
}
