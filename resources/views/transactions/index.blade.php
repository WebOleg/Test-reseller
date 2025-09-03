@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>All Transactions</h1>
            <a href="{{ route('payment.form') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Funds
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Sub User</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'refund' ? 'info' : 'danger') }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($transaction->amount, 2) }}</td>
                                    <td>
                                        @if($transaction->subUser)
                                            <a href="{{ route('sub-users.show', $transaction->subUser) }}">
                                                {{ $transaction->subUser->username }}
                                            </a>
                                        @else
                                            <span class="text-muted">Account</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->description ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $transactions->links() }}
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h4>No transactions yet</h4>
                        <p class="text-muted">Start by adding funds to your account.</p>
                        <a href="{{ route('payment.form') }}" class="btn btn-primary">Add Funds</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
