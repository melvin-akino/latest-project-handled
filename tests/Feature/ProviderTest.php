<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class ProviderTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertProviderNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/providers/manage', 
                [
                    'name'   => '',
                    'alias' => '',
                    'percentage'   => '',
                    'is_enabled' => 0
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertProviderwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/providers/manage', 
                [
                    'name'   => 'Singbet1111',
                    'alias' => 'Test1',
                    'percentage'   => 45,
                    'is_enabled' => 1
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}
