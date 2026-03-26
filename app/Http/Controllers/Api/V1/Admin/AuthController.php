<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Handle authentication and token issuance.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Determine token abilities and name based on role
        $abilities = [];
        $tokenName = 'PersonalToken';

        if ($user->role === 'super_admin') {
            $abilities = ['all-access'];
            $tokenName = 'OwnerToken';
        } elseif ($user->role === 'local_admin') {
            $abilities = ['local-access'];
            $tokenName = 'EmployeeToken';
        }

        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'local_id' => $user->local_id,
            ]
        ], 'Login exitoso.');
    }
}
