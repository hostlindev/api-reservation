<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AvailabilityService;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date'     => 'required|date_format:Y-m-d',
            'time'     => 'nullable|date_format:H:i',
            'court_id' => 'nullable|integer|exists:courts,id'
        ]);

        try {
            // Flujo 1: Búsqueda por Fecha y Hora (Muestra canchas)
            if (isset($validated['time']) && !isset($validated['court_id'])) {
                $courts = $this->availabilityService->getAvailableCourts(
                    $validated['date'],
                    $validated['time']
                );
                
                return response()->json([
                    'success' => true,
                    'type'    => 'courts_search',
                    'data'    => $courts
                ]);
            }

            // Flujo 2: Búsqueda por Cancha (Generará las horas disponibles)
            if (isset($validated['court_id'])) {
                $slots = $this->availabilityService->getAvailableSlots(
                    $validated['court_id'],
                    $validated['date']
                );

                return response()->json([
                    'success' => true,
                    'type'    => 'time_slots_search',
                    'data'    => $slots
                ]);
            }
            
            // Flujo 3: Solo Fecha (Genera todas las canchas con sus slots disponibles)
            if (!isset($validated['time']) && !isset($validated['court_id'])) {
                $courts = \App\Models\Court::with('local')->where('status', 'active')->get();
                $availableCourtsWithSlots = [];

                foreach ($courts as $court) {
                    $slots = $this->availabilityService->getAvailableSlots(
                        $court->id,
                        $validated['date']
                    );

                    if (!empty($slots)) {
                        $courtData = $court->toArray();
                        $courtData['available_slots'] = $slots;
                        $availableCourtsWithSlots[] = $courtData;
                    }
                }

                return response()->json([
                    'success' => true,
                    'type'    => 'all_courts_slots',
                    'data'    => $availableCourtsWithSlots
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos.'
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error fetching availability:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al procesar la disponibilidad.'
            ], 500);
        }
    }
}
