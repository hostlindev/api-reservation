<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of the courts for the authenticated admin's local.
     */
    public function index()
    {
        // TenantScope automatically filters this
        $courts = Court::all();
        return $this->successResponse($courts, 'Courts retrieved successfully.');
    }

    /**
     * Store a newly created court in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'category' => 'required|string',
            'name' => 'required|string',
            'number' => 'required|string',
            'price_per_hour' => 'required|numeric|min:0',
            'status' => 'in:active,inactive'
        ];

        // If user is a super_admin, they must provide the local_id manually
        if (auth()->user()->role === 'super_admin') {
            $rules['local_id'] = 'required|exists:locals,id';
        }

        $validated = $request->validate($rules);

        // If local admin, force local_id to be their own
        if (auth()->user()->role === 'local_admin') {
            $validated['local_id'] = auth()->user()->local_id;
        }

        $court = Court::create($validated);

        return $this->successResponse($court, 'Court created successfully.', 201);
    }

    /**
     * Display the specified court.
     */
    public function show(Court $court)
    {
        // TenantScope ensures they can only see their own courts
        return $this->successResponse($court, 'Court retrieved successfully.');
    }

    /**
     * Update the specified court in storage.
     */
    public function update(Request $request, Court $court)
    {
        $validated = $request->validate([
            'category' => 'sometimes|string',
            'name' => 'sometimes|string',
            'number' => 'sometimes|string',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $court->update($validated);

        return $this->successResponse($court, 'Court updated successfully.');
    }

    /**
     * Remove the specified court from storage.
     */
    public function destroy(Court $court)
    {
        $court->delete();
        return $this->successResponse(null, 'Court deleted successfully.');
    }
}
