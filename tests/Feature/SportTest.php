<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class SportTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertSportNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/sports/manage', 
                [
                    'sport'     => '',
                    'icon'      => '',
                    'slug'      => '',
                    'priority'  => 0,
                    'details'   => '',
                    'is_enabled'=> false
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertSportwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/sports/manage', 
                [
                    'sport'     => 'Hockey',
                    'icon'      => 'sports_hockey',
                    'slug'      => 'hockey',
                    'priority'  => 100,
                    'details'   => 'Ice Hockey',
                    'is_enabled'=> true
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}