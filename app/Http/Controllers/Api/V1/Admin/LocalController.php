<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Local;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocalController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of locals.
     * Only accessible by super_admin.
     */
    public function index()
    {
        $locals = Local::all();
        return $this->successResponse($locals, 'Locals retrieved successfully.');
    }

    /**
     * Store a newly created local in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'min_booking_duration' => 'nullable|integer|min:30',
            'schedule_config' => 'nullable|array',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        $validated['min_booking_duration'] = $validated['min_booking_duration'] ?? 120;

        $local = Local::create($validated);

        return $this->successResponse($local, 'Local created successfully.', 201);
    }

    /**
     * Display the specified local.
     */
    public function show(Local $local)
    {
        return $this->successResponse($local, 'Local retrieved successfully.');
    }

    /**
     * Update the specified local in storage.
     */
    public function update(Request $request, Local $local)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:255',
            'min_booking_duration' => 'sometimes|integer|min:30',
            'schedule_config' => 'nullable|array',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . uniqid();
        }

        $local->update($validated);

        return $this->successResponse($local, 'Local updated successfully.');
    }

    /**
     * Remove the specified local from storage.
     */
    public function destroy(Local $local)
    {
        $local->delete();
        return $this->successResponse(null, 'Local deleted successfully.');
    }
}
