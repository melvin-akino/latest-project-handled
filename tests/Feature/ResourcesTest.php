<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\{RefreshDatabase, WithFaker};
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function timezonesTest()
    {
        $response = $this->get('/api/v1/timezones');
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function sportsOddsTest()
    {
        $response = $this->get('/api/v1/sports/odds');

        $response->assertJson(['status_code' => 200]);
    }
}
