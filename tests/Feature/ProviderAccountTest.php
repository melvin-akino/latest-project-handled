<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class ProviderAccountTest extends AdminAccountTest
{
  
    /** @test */
    public function InsertProviderAccountNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/provider_accounts/manage', 
                [
                    'username'   => '',
                    'password' => '',
                    'provider_id' => 0,
                    'account_type' => '',
                    'credits' => 0,
                    'pa_percentage'   => 0,
                    'pa_is_enabled' => 0,
                    'is_idle' => 0
                ]
            );
       
         $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertProviderAccountwithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/provider_accounts/manage', 
                [
                    'providerAccountId' => '',
                    'username'   => 'NPTdev1',
                    'password' => 'pass8888',
                    'provider_id' => '2',
                    'account_type' => 'BET_NORMAL',
                    'credits' => 0,
                    'pa_percentage'   => '45',
                    'pa_is_enabled' => 1,
                    'is_idle' => 1
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}