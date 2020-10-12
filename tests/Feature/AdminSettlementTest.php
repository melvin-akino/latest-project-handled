<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class AdminSettlementTest extends AdminAccountTest
{
    
    /** @test */
    public function InsertAdminSettlementNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/unsettled_transactions/generate_settlement', 
                [
                    'provider'      => '',
                    'sport'         => 0,
                    'username'      => '',
                    'status'        => '',
                    'odds'          => 0,
                    'score'         => '',
                    'stake'         => 0,
                    'pl'            => 0,
                    'reason'        => '',
                    'payload'       => '',
                    'bet_id'        => '',
                    'processed'     => false
                ]
            );
        
        $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertAdminSettlementwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/unsettled_transactions/generate_settlement', 
                [
                    'provider'      => 'HG',
                    'sport'         => 1,
                    'username'      => 'abc12345',
                    'status'        => 'WIN',
                    'odds'          => 0.99,
                    'score'         => '10-0',
                    'stake'         => 100,
                    'pl'            => 99,
                    'reason'        => 'Admin settlement test reason',
                    'payload'       => 'Sample serialized data of the payload array',
                    'bet_id'        => 'AW123456789',
                    'processed'     => false
                ]
            );
        $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}