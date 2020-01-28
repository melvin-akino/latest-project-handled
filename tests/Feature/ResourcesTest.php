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
    public function statesTest()
    {
        $response = $this->get('/api/v1/states/1');
        $response->assertJson(['status_code' => 200]);
    }

    /** @test */
    public function citiesTest()
    {
        $response = $this->get('/api/v1/cities/1');
        $response->assertJson(['status_code' => 200]);
    }
}
