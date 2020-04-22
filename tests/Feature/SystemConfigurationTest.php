<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class SystemConfigurationTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertSystemConfigurationNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/system_configurations/manage', 
                [
                    'id'        => '',
                    'type'      => '',
                    'value'     => '',
                    'module'    => ''
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function UpdateSystemConfigurationwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/system_configurations/manage', 
                [
                    'id'        => 1,
                    'type'      => 'SCHEDULE_INPLAY_TIMER',
                    'value'     => 1,
                    'module'    => ''
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}
