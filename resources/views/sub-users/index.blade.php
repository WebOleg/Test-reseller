@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Sub Users</h1>
            <a href="{{ route('sub-users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Sub User
            </a>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('sub-users.index') }}" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search username or email..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('sub-users.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                @if($subUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subUsers as $subUser)
                                <tr>
                                    <td>{{ $subUser->username }}</td>
                                    <td>{{ $subUser->email }}</td>
                                    <td>${{ number_format($subUser->balance, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $subUser->status === 'active' ? 'success' : ($subUser->status === 'inactive' ? 'secondary' : 'danger') }}">
                                            {{ ucfirst($subUser->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $subUser->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('sub-users.show', $subUser) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('sub-users.edit', $subUser) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('sub-users.destroy', $subUser) }}" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this sub user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Custom styled pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing {{ $subUsers->firstItem() }} to {{ $subUsers->lastItem() }} of {{ $subUsers->total() }} results
                        </div>
                        
                        @if ($subUsers->hasPages())
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($subUsers->onFirstPage())
                                        <li class="page-item disabled"><span class="page-link">Previous</span></li>
                                    @else
                                        <li class="page-item"><a class="page-link" href="{{ $subUsers->previousPageUrl() }}" rel="prev">Previous</a></li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($subUsers->getUrlRange(1, $subUsers->lastPage()) as $page => $url)
                                        @if ($page == $subUsers->currentPage())
                                            <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($subUsers->hasMorePages())
                                        <li class="page-item"><a class="page-link" href="{{ $subUsers->nextPageUrl() }}" rel="next">Next</a></li>
                                    @else
                                        <li class="page-item disabled"><span class="page-link">Next</span></li>
                                    @endif
                                </ul>
                            </nav>
                        @endif
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No sub users found</h4>
                        <p class="text-muted">Create your first sub user to get started.</p>
                        <a href="{{ route('sub-users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Sub User
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
