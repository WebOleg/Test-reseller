<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = auth()->user()->transactions()
            ->with('subUser')
            ->latest()
            ->paginate(20);
            
        return view('transactions.index', compact('transactions'));
    }
}
