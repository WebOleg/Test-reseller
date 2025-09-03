<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\SubUser;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Basic stats for testing
        $stats = [
            'total_transactions' => $user->transactions()->count(),
            'total_spent' => $user->transactions()->where('type', 'charge')->sum('amount'),
            'total_deposited' => $user->transactions()->where('type', 'deposit')->sum('amount'),
            'active_sub_users' => $user->subUsers()->where('status', 'active')->count(),
            'avg_transaction' => $user->transactions()->avg('amount') ?? 0,
        ];
        
        // Simple chart data for testing
        $chartData = [
            'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
            'deposits' => [100, 200, 150, 300, 250],
            'charges' => [50, 75, 100, 125, 90],
            'refunds' => [0, 10, 5, 15, 8],
        ];
        
        $topSubUsers = $user->subUsers()->limit(5)->get()->toArray();
        $period = '7days';
        
        return view('statistics.index', compact('stats', 'chartData', 'topSubUsers', 'period'));
    }
}
