@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Add Funds</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <strong>Current Balance: ${{ number_format(auth()->user()->balance ?? 0, 2) }}</strong>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('payment.process') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="amount" step="0.01" min="1" max="1000" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Choose method...</option>
                                <option value="card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                                <option value="crypto">Crypto</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Process Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
