<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function showForm()
    {
        return view('payments.form');
    }

    public function process(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000',
            'payment_method' => 'required|in:card,paypal,crypto'
        ]);

        $user = auth()->user();
        $amount = $request->amount;
        $transactionId = 'tx_' . uniqid();

        DB::beginTransaction();

        $success = rand(1, 10) > 1;

        if ($success) {
            $user->increment('balance', $amount);
            
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => 'Payment via ' . $request->payment_method,
                'reference_id' => $transactionId,
                'status' => 'completed'
            ]);
            
            DB::commit();
            return back()->with('success', 'Payment successful! Added $' . number_format($amount, 2) . ' to balance.');
            
        } else {
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => 'Failed payment via ' . $request->payment_method,
                'reference_id' => $transactionId,
                'status' => 'failed'
            ]);
            
            DB::commit();
            return back()->with('error', 'Payment failed! Try again.');
        }
    }
}
