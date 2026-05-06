<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponser;

    /**
     * List all staff members for the local.
     */
    public function index()
    {
        // TenantScope automatically filters users by local_id
        $users = User::where('role', 'staff')->get();
            
        return $this->successResponse($users, 'Staff list retrieved successfully.');
    }

    /**
     * Create a new staff member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
            'local_id' => auth()->user()->local_id,
        ]);

        return $this->successResponse($user, 'Staff member created successfully.', 201);
    }
    
    /**
     * Remove a staff member.
     */
    public function destroy(User $user)
    {
        // TenantScope ensures they can only access users from their own local
        if ($user->role !== 'staff') {
            return $this->errorResponse('Only staff members can be deleted via this endpoint.', 403);
        }
        
        $user->delete();
        return $this->successResponse(null, 'Staff member deleted successfully.');
    }
}
