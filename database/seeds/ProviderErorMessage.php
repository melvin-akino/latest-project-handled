<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderErorMessage extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected $tablename = "provider_error_messages";
    public function run()
    {
        //
  
        $messages = [
        		[
        			'message' => 'Your bet is currently pending',
        			'error'   =>'Your bet is currently pending.'
        		],

        		[
        			'message' => 'Rejected',
        			'error'	  => 'Rejected.',

        		],
        		[
        			'message' => 'The maximum bet amount for this event is RMB 5000.16',
        			'error'	  => 'Bet was not placed. Please try again.'
        		],
       
        		[
        			'message' => 'The odds have changed. Please try again.',
        			'error'	  => 'The odds have changed. Please try again.'	
        		],
        		[
        			'message' => 'The odds have changed: 1.05. Please try again',
        			'error'	  => 'The odds have changed. Please try again.'	
        		],
        		
        		[
        			'message' =>'Your bet was not placed. Please try again.156',
        			'error'	  => 'Bet was not placed. Please try again.'	
        		],
        		[
        			'message' => 'Abnormal Bets',
        			'error'	  => 'Abnormal bet.'	
        		],
        		[	'message' => "Bookmaker can't be reached",
        			'error'	  => 'Bookmaker cannot be reached. '
        		],
        		[
        			'message' => 'Internal Error: Session Inactive',
        			'error'	  => 'Internal Error. Please contact support.'	
        		]

        ];
        foreach($messages as $m) {
        	$message = $m['message'];
        	$error 	 = $m['error'];
        	$db 	 = DB::table('error_messages')->where('error',$error)->first();
        	if ($db) {
        		$data =[
    					'message' 			=> $message,
    					'error_message_id'  => $db->id,
    					'created_at' 		=> Carbon::now(),
            			'updated_at'		=> Carbon::now()
        			];
        		DB::table($this->tablename)->insert($data);

        	}

        	
        }
    }
}
