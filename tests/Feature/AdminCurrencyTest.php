<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\{WithoutMiddleware,RefreshDatabase,WithFaker};
use Tests\TestCase;

class AdminCurrencyTest extends AdminAccountTest
{
  
    /** @test */
    public function InsertCurrenciesNodataTest() {
              
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/wallet/currencies', 
                [
                    'currency_name'   => '',
                    'currency_symbol' => '',
                    'currency_code'   => ''
                ]
            );
       
         $response->assertStatus(422);
        
    }
     /** @test */
    public function InsertCurrencieswithRecordTest() {
         
        $this->login();
        $response = $this->actingAs($this->user)->json('POST', 'admin/wallet/currencies', 
                [
                    'currency_name'   => 'php',
                    'currency_symbol' => 'Php',
                    'currency_code'   => 'php'
                ]
            );
       
         $response->assertStatus(200);
    }
    
    public function login(){
    
        $this->withoutMiddleware();
        return $this->LoginwithFakeUser();
    }
}
