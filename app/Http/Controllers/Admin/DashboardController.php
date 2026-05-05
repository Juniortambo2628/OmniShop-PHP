<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filterEvent  = $request->input('event');
        $filterStatus = $request->input('status');
        $filterSearch = $request->input('q');

        $events = config('events');
        $statuses = ['Pending', 'Approved', 'Invoiced', 'Fulfilled', 'Cancelled'];

        $query = \App\Models\Order::query();

        if ($filterEvent) {
            $query->where('event_slug', $filterEvent);
        }
        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }
        if ($filterSearch) {
            $query->where(function($q) use ($filterSearch) {
                $q->where('order_id', 'like', "%{$filterSearch}%")
                  ->orWhere('company_name', 'like', "%{$filterSearch}%")
                  ->orWhere('contact_name', 'like', "%{$filterSearch}%")
                  ->orWhere('booth_number', 'like', "%{$filterSearch}%");
            });
        }

        $orders = $query->latest()->get();

        // Stats
        $statsQuery = \App\Models\Order::query();
        if ($filterEvent) $statsQuery->where('event_slug', $filterEvent);
        
        $stats = [
            'total_orders' => $statsQuery->count(),
            'total_revenue' => $statsQuery->sum('total'),
            'pending_orders' => $statsQuery->clone()->where('status', 'Pending')->count(),
            'paid_orders' => $statsQuery->clone()->whereIn('status', ['Approved', 'Invoiced', 'Fulfilled'])->count(),
        ];

        return view('admin.dashboard', compact('orders', 'events', 'statuses', 'stats', 'filterEvent', 'filterStatus', 'filterSearch'));
    }

    public function loginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('admin');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
