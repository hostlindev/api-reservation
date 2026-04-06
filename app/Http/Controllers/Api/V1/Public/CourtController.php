<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    use ApiResponser;

    /**
     * Display a listing of all active courts.
     */
    public function index(Request $request)
    {
        $query = Court::where('status', 'active')->with('local');

        // Optional filtering by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Optional filtering by local
        if ($request->has('local_id')) {
            $query->where('local_id', $request->local_id);
        }

        $courts = $query->get();

        return $this->successResponse($courts, 'Courts retrieved successfully.');
    }

    /**
     * Display a specific court.
     */
    public function show(Court $court)
    {
        // Only allow viewing active courts (optional, or return 404 if inactive)
        if ($court->status !== 'active') {
            return $this->errorResponse('Court not found or inactive.', 404);
        }

        $court->load('local');

        return $this->successResponse($court, 'Court retrieved successfully.');
    }
    /**
     * Devuelve las fechas que tienen al menos un periodo libre según la duración mínima.
     */
    public function getAvailableDates(Court $court, Request $request, \App\Services\AvailabilityService $availabilityService)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : now()->startOfDay();
        // Por defecto buscamos hasta 30 días en adelante si no proveen end_date
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date)->startOfDay() : $startDate->copy()->addDays(30);

        $availableDates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $slots = $availabilityService->getAvailableSlots($court->id, $date->format('Y-m-d'));
            
            // Si hay al menos un slot disponible, consideramos el día seleccionable
            if (count($slots) > 0) {
                $availableDates[] = $date->format('Y-m-d');
            }
        }

        return $this->successResponse($availableDates, 'Available dates retrieved successfully.');
    }

    /**
     * Devuelve los slots de horas 100% fraccionadas y disponibles para un día específico.
     */
    public function getAvailableBlocks(Court $court, Request $request, \App\Services\AvailabilityService $availabilityService)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $slots = $availabilityService->getAvailableSlots($court->id, $request->date);

        // Mapeamos al formato enriquecido para respetar contratos previos de frontend y añadir estandarización.
        $mappedSlots = array_map(function ($slot) {
            $startCarbon = \Carbon\Carbon::parse($slot['start_time']);
            $endCarbon = \Carbon\Carbon::parse($slot['end_time']);
            return [
                'inicio'     => $startCarbon->format('h:i A'),
                'fin'        => $endCarbon->format('h:i A'),
                'start_time' => $slot['start_time'],
                'end_time'   => $slot['end_time']
            ];
        }, $slots);

        return $this->successResponse($mappedSlots, 'Available blocks retrieved successfully.');
    }
}
