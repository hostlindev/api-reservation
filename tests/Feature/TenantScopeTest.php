<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Local;
use App\Models\Court;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected $adminCostaDelEste;
    protected $adminSanFrancisco;
    protected $localCostaDelEste;
    protected $localSanFrancisco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localCostaDelEste = Local::create([
            'name' => 'Centro Costa del Este',
            'slug' => 'centro-cde',
        ]);

        $this->localSanFrancisco = Local::create([
            'name' => 'Club San Francisco',
            'slug' => 'club-sf',
        ]);

        $this->adminCostaDelEste = User::create([
            'name' => 'Admin CDE',
            'email' => 'admin.cde@test.com',
            'password' => bcrypt('password'),
            'role' => 'local_admin',
            'local_id' => $this->localCostaDelEste->id
        ]);

        $this->adminSanFrancisco = User::create([
            'name' => 'Admin SF',
            'email' => 'admin.sf@test.com',
            'password' => bcrypt('password'),
            'role' => 'local_admin',
            'local_id' => $this->localSanFrancisco->id
        ]);

        // Creating courts
        Court::create([
            'local_id' => $this->localCostaDelEste->id,
            'category' => 'Tenis',
            'name' => 'Cancha 1 CDE',
            'number' => '1',
            'price_per_hour' => 15
        ]);

        Court::create([
            'local_id' => $this->localSanFrancisco->id,
            'category' => 'Padel',
            'name' => 'Cancha 1 SF',
            'number' => '1',
            'price_per_hour' => 20
        ]);
        Court::create([
            'local_id' => $this->localSanFrancisco->id,
            'category' => 'Padel',
            'name' => 'Cancha 2 SF',
            'number' => '2',
            'price_per_hour' => 20
        ]);
    }

    /** @test */
    public function local_admin_only_sees_their_own_courts()
    {
        // Act as Admin Costa del Este
        Sanctum::actingAs($this->adminCostaDelEste);

        $response = $this->getJson('/api/v1/admin/courts');

        // Should return 200 and only 1 court (from CDE)
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('Cancha 1 CDE', $response->json('data.0.name'));
    }

    /** @test */
    public function different_admin_sees_their_own_courts()
    {
        // Act as Admin San Francisco
        Sanctum::actingAs($this->adminSanFrancisco);

        $response = $this->getJson('/api/v1/admin/courts');

        // Should return 200 and 2 courts (from SF)
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /** @test */
    public function query_builder_returns_filtered_results()
    {
        // Test eloquent query
        $this->actingAs($this->adminCostaDelEste);
        $this->assertEquals(1, Court::count());

        $this->actingAs($this->adminSanFrancisco);
        $this->assertEquals(2, Court::count());
    }
}
