<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Local;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class LocalController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of all active locals.
     */
    public function index()
    {
        // Typically you'll want to list all public locals here
        $locals = Local::all();
        return $this->successResponse($locals, 'Locals retrieved successfully.');
    }

    /**
     * Display the specified local along with its active courts.
     */
    public function show(Local $local)
    {
        // Load only active courts for the public wrapper
        $local->load(['courts' => function ($query) {
            $query->where('status', 'active');
        }]);

        return $this->successResponse($local, 'Local info and courts retrieved successfully.');
    }
}
