<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class AdminSettlementTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertProviderNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/unsettled_transactions/generate_settlement', 
                [
                    'reason' => '',
                    'payload' => '',
                    'bet_id' => '',
                    'processed' => '',
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertProviderwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/unsettled_transactions/generate_settlement', 
                [
                    'reason' => 'Admin settlement test reason',
                    'payload' => 'Sample serialized data of the payload array',
                    'bet_id' => 'AW123456789',
                    'processed' => true,
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}