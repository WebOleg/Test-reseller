@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>{{ $subUser->username }}</h1>
            <div>
                <a href="{{ route('sub-users.edit', $subUser) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('sub-users.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>User Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> {{ $subUser->username }}</p>
                <p><strong>Email:</strong> {{ $subUser->email }}</p>
                <p><strong>Balance:</strong> ${{ number_format($subUser->balance, 2) }}</p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-{{ $subUser->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($subUser->status) }}
                    </span>
                </p>
                <p><strong>Created:</strong> {{ $subUser->created_at->format('M d, Y H:i') }}</p>
                <p><strong>API ID:</strong> {{ $subUser->api_user_id ?? 'Not synced' }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        @if($subUser->api_user_id)
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-globe"></i> Proxy Connection Details
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($proxyDetails))
                        <div class="mb-3">
                            <label class="form-label"><strong>Proxy Server:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="proxy.dataimpulse.com:8080" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Username:</strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ $proxyDetails['login'] }}" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Password:</strong></label>
                            <div class="input-group">
                                <input type="password" class="form-control" value="{{ $proxyDetails['password'] }}" readonly id="proxyPassword">
                                <button class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard(document.getElementById('proxyPassword'))">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Threads:</strong></label>
                            <input type="text" class="form-control" value="{{ $proxyDetails['threads'] }}" readonly>
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Usage Example:</strong><br>
                            <code>curl -x proxy.dataimpulse.com:8080 -U {{ $proxyDetails['login'] }}:{{ $proxyDetails['password'] }} https://httpbin.org/ip</code>
                        </div>
                    @else
                        <div class="text-center">
                            <button class="btn btn-primary" onclick="loadProxyDetails({{ $subUser->api_user_id }})">
                                <i class="fas fa-sync"></i> Load Proxy Details
                            </button>
                        </div>
                        <div id="proxyDetailsContainer"></div>
                    @endif
                </div>
            </div>
        @else
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> API Not Synced
                    </h5>
                </div>
                <div class="card-body">
                    <p>This sub-user is not synchronized with DataImpulse API.</p>
                    <p class="text-muted">Proxy credentials are not available.</p>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Transactions</h5>
            </div>
            <div class="card-body">
                @forelse($transactions as $transaction)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <span class="badge bg-{{ $transaction->type === 'charge' ? 'danger' : 'success' }}">
                                {{ ucfirst($transaction->type) }}
                            </span>
                            <small class="text-muted d-block">{{ $transaction->description ?? 'No description' }}</small>
                        </div>
                        <div class="text-end">
                            <strong>${{ number_format($transaction->amount, 2) }}</strong><br>
                            <small class="text-muted">{{ $transaction->created_at->format('M d, H:i') }}</small>
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
function copyToClipboard(button) {
    const input = button.parentElement.querySelector('input');
    input.select();
    document.execCommand('copy');
    
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}

function togglePassword() {
    const passwordField = document.getElementById('proxyPassword');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        eyeIcon.className = 'fas fa-eye';
    }
}

function loadProxyDetails(apiUserId) {
    fetch(`/api/sub-users/${apiUserId}/proxy-details`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('proxyDetailsContainer').innerHTML = `
                <div class="mt-3">
                    <strong>Login:</strong> ${data.login}<br>
                    <strong>Password:</strong> ${data.password}<br>
                    <strong>Threads:</strong> ${data.threads}
                </div>
            `;
        });
}
</script>
@endpush
@endsection
