<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;
use App\Models\ErrorMessage;

class ProviderErrorMessageTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertProviderErrorNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/message/create', 
                [
                    'message'   => '',
                ]
            );
        
        $response->assertStatus(422);
        
    }
    

     /** @test */
     
    public function InsertProviderErrorMessagewithRecordTest() {
         
        $this->login();

        $response = $this->actingAs($this->user)->json('POST', 'admin/message/create', 
                [
                    'message'  => 'This data is for testing',
                    'error_id' => 1
                ]
            );
       
         $response->assertStatus(200);
    }

  
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}
