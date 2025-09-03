@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Total Sub Users</h6>
                        <h3>{{ $totalSubUsers }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Active Users</h6>
                        <h3>{{ $activeSubUsers }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Total Balance</h6>
                        <h3>${{ number_format($totalBalance, 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6>Your Balance</h6>
                        <h3>${{ number_format(auth()->user()->balance ?? 0, 2) }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
                <a href="{{ route('payment.form') }}" class="btn btn-light btn-sm mt-2">Add Funds</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Monthly Spending</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h5>Recent Transactions</h5>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
            <div class="card-body">
                @forelse($recentTransactions as $transaction)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <span class="badge bg-{{ $transaction->type === 'charge' ? 'danger' : ($transaction->type === 'deposit' ? 'success' : 'info') }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                            <small class="text-muted d-block">
                                {{ $transaction->subUser->username ?? 'Account' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <strong>${{ number_format($transaction->amount, 2) }}</strong>
                            <small class="text-muted d-block">{{ $transaction->created_at->format('M d, H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No transactions yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($monthlySpending->pluck('month')),
        datasets: [{
            label: 'Monthly Spending',
            data: @json($monthlySpending->pluck('total')),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
@endsection
