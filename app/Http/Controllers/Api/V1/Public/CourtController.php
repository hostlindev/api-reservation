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
     * Devuelve las fechas que tienen al menos un hueco libre de 2 horas.
     */
    public function getAvailableDates(Court $court, Request $request)
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
            $blocks = $this->calculateAvailableBlocks($court, $date);
            
            // Si hay al menos un bloque (lo cual ya implica >= 120 min), consideramos el día seleccionable
            if (count($blocks) > 0) {
                $availableDates[] = $date->format('Y-m-d');
            }
        }

        return $this->successResponse($availableDates, 'Available dates retrieved successfully.');
    }

    /**
     * Devuelve los bloques de horas disponibles para un día específico (mínimo 2 horas).
     */
    public function getAvailableBlocks(Court $court, Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d'
        ]);

        $date = \Carbon\Carbon::parse($request->date)->startOfDay();
        $blocks = $this->calculateAvailableBlocks($court, $date);

        return $this->successResponse($blocks, 'Available blocks retrieved successfully.');
    }

    /**
     * Lógica central de filtrado de intervalos utilizando Carbon.
     */
    private function calculateAvailableBlocks(Court $court, \Carbon\Carbon $date)
    {
        // Rango operativo: 16:00 a 00:00 (inicio del día siguiente)
        $startOfDay = $date->copy()->setTime(16, 0, 0);
        $endOfDay   = $date->copy()->addDay()->setTime(0, 0, 0);

        // Si la hora actual ya superó las 00:00 y estamos consultando el día actual, 
        // podríamos ajustar para que no devuelva horas pasadas, 
        // pero evaluemos desde las 16:00 siempre o desde "now()".
        if ($date->isToday() && now()->max($startOfDay)->lt($endOfDay)) {
           $startOfDay = now()->max($startOfDay);
        } else if ($date->isBefore(now()->startOfDay())) {
            return []; // Día en el pasado
        }

        // Obtener Reservas y Bloqueos en ese rango
        $bookings = $court->bookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($q) use ($startOfDay, $endOfDay) {
                $q->whereBetween('start_time', [$startOfDay, $endOfDay])
                  ->orWhereBetween('end_time', [$startOfDay, $endOfDay])
                  ->orWhere(function ($sub) use ($startOfDay, $endOfDay) {
                      $sub->where('start_time', '<=', $startOfDay)
                          ->where('end_time', '>=', $endOfDay);
                  });
            })
            ->get();

        $locks = $court->bookingLocks()
            ->where('expires_at', '>', now())
            ->where(function ($q) use ($startOfDay, $endOfDay) {
                $q->whereBetween('start_time', [$startOfDay, $endOfDay])
                  ->orWhereBetween('end_time', [$startOfDay, $endOfDay])
                  ->orWhere(function ($sub) use ($startOfDay, $endOfDay) {
                      $sub->where('start_time', '<=', $startOfDay)
                          ->where('end_time', '>=', $endOfDay);
                  });
            })
            ->get();

        // Juntar y ordenar cronológicamente
        $occupied = collect();
        foreach ($bookings as $b) {
            $occupied->push([
                'start' => \Carbon\Carbon::parse($b->start_time)->max($startOfDay),
                'end'   => \Carbon\Carbon::parse($b->end_time)->min($endOfDay)
            ]);
        }
        foreach ($locks as $l) {
            $occupied->push([
                'start' => \Carbon\Carbon::parse($l->start_time)->max($startOfDay),
                'end'   => \Carbon\Carbon::parse($l->end_time)->min($endOfDay)
            ]);
        }

        $occupied = $occupied->sortBy('start')->values();

        // Unir reservas solapadas (ej. 16:00-18:00 y 17:30-19:00 -> 16:00-19:00)
        $mergedOccupied = [];
        if ($occupied->isNotEmpty()) {
            $current = $occupied[0];
            for ($i = 1; $i < $occupied->count(); $i++) {
                if ($occupied[$i]['start']->lte($current['end'])) {
                    $current['end'] = $current['end']->max($occupied[$i]['end']);
                } else {
                    $mergedOccupied[] = $current;
                    $current = $occupied[$i];
                }
            }
            $mergedOccupied[] = $current;
        }

        // Calcular los espacios libres
        $availableBlocks = [];
        $currentStart = $startOfDay;

        foreach ($mergedOccupied as $block) {
            if ($currentStart->lt($block['start'])) {
                $duration = $currentStart->diffInMinutes($block['start']);
                // Regla principal: Hueco continuo de mínimo 2 horas (120 minutos)
                if ($duration >= 120) {
                    $availableBlocks[] = [
                        'inicio' => $currentStart->format('h:i A'),
                        'fin'    => $block['start']->format('H:i') === '00:00' ? '12:00 AM' : $block['start']->format('h:i A')
                    ];
                }
            }
            $currentStart = $currentStart->max($block['end']);
        }

        // Evaluar el tramo final del día
        if ($currentStart->lt($endOfDay)) {
            $duration = $currentStart->diffInMinutes($endOfDay);
            if ($duration >= 120) {
                $availableBlocks[] = [
                    'inicio' => $currentStart->format('h:i A'),
                    'fin'    => $endOfDay->format('H:i') === '00:00' ? '12:00 AM' : $endOfDay->format('h:i A')
                ];
            }
        }

        return $availableBlocks;
    }
}
