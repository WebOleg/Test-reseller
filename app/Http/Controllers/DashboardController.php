<?php

namespace App\Http\Controllers;

use App\Models\SubUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display dashboard with statistics
     */
    public function index()
    {
        $user = auth()->user();
        
        // Basic stats
        $totalSubUsers = $user->subUsers()->count();
        $activeSubUsers = $user->activeSubUsers()->count();
        $totalBalance = $user->subUsers()->sum('balance');
        
        // Recent transactions
        $recentTransactions = $user->transactions()
            ->with('subUser')
            ->latest()
            ->limit(10)
            ->get();
            
        // Monthly spending chart data
        $monthlySpending = $user->transactions()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('type', 'charge')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard', compact(
            'totalSubUsers',
            'activeSubUsers', 
            'totalBalance',
            'recentTransactions',
            'monthlySpending'
        ));
    }
}
