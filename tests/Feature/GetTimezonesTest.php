<?php

namespace Tests\Feature;

use App\Models\Timezones;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetTimezonesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function getTimezones()
    {
        $response = $this->get('/api/v1/timezones');

        $response->assertStatus(200);
    }
}
