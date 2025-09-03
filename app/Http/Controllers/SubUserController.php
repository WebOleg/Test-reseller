<?php

namespace App\Http\Controllers;

use App\Models\SubUser;
use App\Http\Requests\StoreSubUserRequest;
use App\Http\Requests\UpdateSubUserRequest;
use App\Services\ResellerApi\ResellerApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubUserController extends Controller
{
    use AuthorizesRequests;
    
    protected ResellerApiClient $apiClient;

    public function __construct(ResellerApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function index(Request $request)
    {
        $query = SubUser::where('user_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subUsers = $query->latest()->paginate(15);
        return view('sub-users.index', compact('subUsers'));
    }

    public function create()
    {
        return view('sub-users.create');
    }

    public function store(StoreSubUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $subUser = SubUser::create([
                'user_id' => auth()->id(),
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'balance' => $request->balance ?? 0.00,
                'status' => 'active',
            ]);

            try {
                Log::info('Creating sub user via API', ['username' => $request->username]);
                
                $apiResponse = $this->apiClient->createSubUser([
                    'username' => $request->username,
                    'threads' => 50,
                    'allowed_ips' => [],
                    'sticky_range' => [
                        'start' => 10000,
                        'end' => 20000
                    ]
                ]);

                Log::info('API Response received', ['response' => $apiResponse]);

                $subUser->update([
                    'api_user_id' => $apiResponse['id'] ?? null
                ]);
                
                $successMessage = 'Sub user created successfully and synced with DataImpulse API (ID: ' . ($apiResponse['id'] ?? 'unknown') . ')';
                
            } catch (\Exception $apiException) {
                Log::warning('API sync failed', [
                    'error' => $apiException->getMessage(),
                    'sub_user_id' => $subUser->id
                ]);
                
                $successMessage = 'Sub user created successfully (API sync pending - ' . $apiException->getMessage() . ')';
            }

            DB::commit();

            return redirect()->route('sub-users.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create sub user: ' . $e->getMessage()]);
        }
    }

    public function show(SubUser $subUser)
    {
        if ($subUser->user_id !== auth()->id()) {
            abort(403);
        }
        
        $transactions = collect();
        $proxyDetails = null;

        // Load proxy details if synced with API
        if ($subUser->api_user_id) {
            try {
                $proxyDetails = $this->apiClient->getSubUser($subUser->api_user_id);
            } catch (\Exception $e) {
                Log::warning('Failed to load proxy details', [
                    'sub_user_id' => $subUser->id,
                    'api_user_id' => $subUser->api_user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return view('sub-users.show', compact('subUser', 'transactions', 'proxyDetails'));
    }

    public function edit(SubUser $subUser)
    {
        if ($subUser->user_id !== auth()->id()) {
            abort(403);
        }
        
        return view('sub-users.edit', compact('subUser'));
    }

    public function update(Request $request, SubUser $subUser)
    {
        if ($subUser->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:sub_users,username,' . $subUser->id,
                'email' => 'required|email|max:255|unique:sub_users,email,' . $subUser->id,
                'password' => 'nullable|string|min:8|confirmed',
                'balance' => 'required|numeric|min:0',
                'status' => 'required|in:active,inactive,suspended',
            ]);

            DB::beginTransaction();

            $data = [
                'username' => $validated['username'],
                'email' => $validated['email'],
                'balance' => $validated['balance'],
                'status' => $validated['status'],
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            $subUser->update($data);
            DB::commit();

            return redirect()->route('sub-users.index')
                ->with('success', 'Sub user updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update sub user: ' . $e->getMessage()]);
        }
    }

    public function destroy(SubUser $subUser)
    {
        if ($subUser->user_id !== auth()->id()) {
            abort(403);
        }
        
        try {
            $subUser->delete();
            return redirect()->route('sub-users.index')
                ->with('success', 'Sub user deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete sub user: ' . $e->getMessage()]);
        }
    }
}
