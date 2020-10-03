<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class ErrorMessageTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertErrorMessageNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/error_messages/manage', 
                [
                    'error'   => ''
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertErrorMessagewithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/error_messages/manage', 
                [
                    'error'   => 'Singbet1111'
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}
