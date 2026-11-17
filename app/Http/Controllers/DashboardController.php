<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function index()
    {
        if (Auth::user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('dashboard', $this->service->data());
    }

    public function adminDashboard()
    {
        return Inertia::render('admin-dashboard', $this->service->data(includeAllRentals: true));
    }
}
