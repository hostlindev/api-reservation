<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Booking;
use App\Models\BookingLock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Flujo 1: Busca canchas libres para una Fecha y Hora específicas.
     */
    public function getAvailableCourts(string $date, string $time): Collection
    {
        $startDateTime = Carbon::parse("{$date} {$time}");
        $dayOfWeek = (string) $startDateTime->dayOfWeekIso; // 1 = Lunes ... 7 = Domingo
        $timeOnly = $startDateTime->format('H:i');

        // 1. Cargamos las chanchas y filtramos de entrada las abiertas
        $courts = Court::with('local')
            ->whereHas('local', function ($query) use ($dayOfWeek, $timeOnly) {
                // Compatible notation for querying JSON in modern Laravel
                $query->where("schedule_config->{$dayOfWeek}->is_open", true)
                      ->where("schedule_config->{$dayOfWeek}->open_time", '<=', $timeOnly);
            })
            ->get();

        // 2. Agrupamos por la duración mínima
        $groupedCourts = $courts->groupBy('local.min_booking_duration');
        $availableCourts = collect();

        foreach ($groupedCourts as $duration => $durationCourts) {
            $endDateTime = $startDateTime->copy()->addMinutes($duration);
            $courtIds = $durationCourts->pluck('id');

            // 3. Traemos los IDs de las canchas QUE SÍ ESTÁN OCUPADAS en este rango
            $unavailableCourtIds = Booking::whereIn('court_id', $courtIds)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_time', '<', $endDateTime)
                ->where('end_time', '>', $startDateTime)
                ->pluck('court_id')
                ->concat(
                    BookingLock::whereIn('court_id', $courtIds)
                        ->where('expires_at', '>', now())
                        ->where('start_time', '<', $endDateTime)
                        ->where('end_time', '>', $startDateTime)
                        ->pluck('court_id')
                )->unique();

            // 4. Mapeamos las canchas y verificamos el horario de cierre (close_time)
            foreach ($durationCourts as $court) {
                if ($unavailableCourtIds->contains($court->id)) {
                    continue; // Está ocupada o bloqueada
                }

                $config = is_array($court->local->schedule_config) 
                          ? $court->local->schedule_config 
                          : json_decode($court->local->schedule_config, true);
                          
                $closeTime = Carbon::parse("{$date} " . $config[$dayOfWeek]['close_time']);

                // La reserva debe culminar antes o justo a la hora de cierre del lugar
                if ($endDateTime->lte($closeTime)) {
                    $availableCourts->push($court);
                }
            }
        }

        return $availableCourts->values();
    }

    /**
     * Flujo 2: Generar y devolver slots libres de una cancha designada por día.
     */
    public function getAvailableSlots(int $courtId, string $date): array
    {
        $court = Court::with('local')->findOrFail($courtId);
        $local = $court->local;
        
        $targetDate = Carbon::parse($date);
        $dayOfWeek = (string) $targetDate->dayOfWeekIso;
        
        $config = is_array($local->schedule_config) 
                  ? $local->schedule_config 
                  : json_decode($local->schedule_config, true);
            
        $dayConfig = $config[$dayOfWeek] ?? null;
        
        // Si el local no abre ese día o no tiene configuración
        if (!$dayConfig || empty($dayConfig['is_open'])) {
            return [];
        }

        $openTime = Carbon::parse("{$date} {$dayConfig['open_time']}");
        $closeTime = Carbon::parse("{$date} {$dayConfig['close_time']}");
        $duration = $local->min_booking_duration; 
        
        // Consultamos TODO el inventario de bookings del día
        $bookings = Booking::where('court_id', $courtId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('end_time', '>', $openTime)
            ->where('start_time', '<', $closeTime)
            ->get(['start_time', 'end_time']);
            
        $locks = BookingLock::where('court_id', $courtId)
            ->where('expires_at', '>', now())
            ->where('end_time', '>', $openTime)
            ->where('start_time', '<', $closeTime)
            ->get(['start_time', 'end_time']);
            
        $slots = [];
        $currentSlotStart = $openTime->copy();
        
        while ($currentSlotStart->copy()->addMinutes($duration)->lte($closeTime)) {
            $currentSlotEnd = $currentSlotStart->copy()->addMinutes($duration);
            $isOverlapping = false;
            
            // Verificamos si existe colisión en los bookings
            foreach ($bookings as $booking) {
                if ($currentSlotStart->lt($booking->end_time) && $currentSlotEnd->gt($booking->start_time)) {
                    $isOverlapping = true;
                    break;
                }
            }
            
            // Verificamos si existe colisión en el cache de locks por concurrencia activa
            if (!$isOverlapping) {
                 foreach ($locks as $lock) {
                    if ($currentSlotStart->lt($lock->end_time) && $currentSlotEnd->gt($lock->start_time)) {
                        $isOverlapping = true;
                        break;
                    }
                }
            }
            
            // Si nadie lo ocupa en este fragmento
            if (!$isOverlapping) {
                $slots[] = [
                    'start_time' => $currentSlotStart->format('H:i'),
                    'end_time'   => $currentSlotEnd->format('H:i'),
                ];
            }
            
            // Subimos al siguiente iterador
            $currentSlotStart->addMinutes($duration);
        }
        
        return $slots;
    }
}
