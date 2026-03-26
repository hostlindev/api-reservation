<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Local;
use App\Models\Court;
use App\Models\User;
use App\Models\BookingLock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\BookingAvailabilityService;
use App\Services\BookingLockService;
use Exception;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected $local;
    protected $court1;
    protected $court2;
    protected $availabilityService;
    protected $lockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->local = Local::create([
            'name' => 'Padel Club San Francisco',
            'slug' => 'padel-sf',
            'min_booking_duration' => 60,
        ]);

        $this->court1 = Court::create([
            'local_id' => $this->local->id,
            'category' => 'Padel Techada',
            'name' => 'Cancha Principal',
            'number' => '1',
            'price_per_hour' => 20.00,
            'status' => 'active'
        ]);

        $this->court2 = Court::create([
            'local_id' => $this->local->id,
            'category' => 'Padel Techada',
            'name' => 'Cancha Secundaria',
            'number' => '2',
            'price_per_hour' => 20.00,
            'status' => 'active'
        ]);

        $this->availabilityService = new BookingAvailabilityService();
        $this->lockService = new BookingLockService($this->availabilityService);
    }

    /** @test */
    public function it_successfully_locks_a_court()
    {
        $startTime = Carbon::tomorrow()->setTime(10, 0, 0);
        $endTime = $startTime->copy()->addMinutes(60);

        $result = $this->lockService->lockCourt($this->local, 'Padel Techada', $startTime, $endTime);

        $this->assertNotNull($result['lock_id']);
        $this->assertDatabaseHas('booking_locks', [
            'id' => $result['lock_id'],
            'court_id' => $this->court1->id // It should pick the first available
        ]);
    }

    /** @test */
    public function it_handles_full_capacity_correctly()
    {
        $startTime = Carbon::tomorrow()->setTime(10, 0, 0);
        $endTime = $startTime->copy()->addMinutes(60);

        // Lock both courts
        $this->lockService->lockCourt($this->local, 'Padel Techada', $startTime, $endTime);
        $this->lockService->lockCourt($this->local, 'Padel Techada', $startTime, $endTime);

        $this->assertEquals(2, BookingLock::count());

        // Attempting a third lock should fail
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The selected time is full for category: Padel Techada");

        $this->lockService->lockCourt($this->local, 'Padel Techada', $startTime, $endTime);
    }
}
