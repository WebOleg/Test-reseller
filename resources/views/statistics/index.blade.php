@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Statistics</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6>Total Transactions</h6>
                <h3>{{ number_format($stats['total_transactions']) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6>Total Spent</h6>
                <h3>${{ number_format($stats['total_spent'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Total Deposited</h6>
                <h3>${{ number_format($stats['total_deposited'], 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Active Sub Users</h6>
                <h3>{{ $stats['active_sub_users'] }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Spending Chart</h5>
            </div>
            <div class="card-body">
                <canvas id="spendingChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Top Sub Users</h5>
            </div>
            <div class="card-body">
                @forelse($topSubUsers as $subUser)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <strong>{{ $subUser['username'] ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $subUser['email'] ?? 'N/A' }}</small>
                        </div>
                        <div class="text-end">
                            <strong>${{ number_format($subUser['balance'] ?? 0, 2) }}</strong>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No sub users yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const ctx = document.getElementById('spendingChart').getContext('2d');
const spendingChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($chartData['labels']),
        datasets: [{
            label: 'Sample Data',
            data: @json($chartData['deposits']),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
    }
});
</script>
@endpush
@endsection
