<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\AdminSettlementRequest;
use App\Models\CRM\{Transaction, AdminSettlement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\KafkaPush;
use Exception;

class TransactionsController extends Controller
{

    public function index() 
    {
        $data = [
            'page_title'       => "Unsettled Transactions",
            'page_description' => "List of all unsettled orders",
            'dashboard_menu'   => true,
        ];
        return view('CRM.transactions.index')->with($data);
    }

    public function unsettled()
    {
        $data['data'] = Transaction::getTransactions(['status' => 'open']);
        return response()->json(!empty($data) ? $data : []);
    }

    public function generate_settlement(AdminSettlementRequest $request) 
    {
        try {
            if (!empty($request)) {
                
                $data = $request->toArray();

                if (!empty($data)) {
                    preg_match_all('!\d+!', $data['bet_id'], $id);
                    $requestId = Str::uuid();
                    //Generate kafka json payload here
                    $payload = [
                        'request_id'    => $requestId,
                        'request_ts'    => getMilliseconds(),
                        'command'       => 'settlement',
                        'sub_command'   => 'transform',
                        'data' => [
                            'provider'      => $data['provider'],
                            'sport'         => $data['sport'],
                            'id'            => $id,
                            'username'      => $data['username'],
                            'status'        => $data['status'],
                            'odds'          => $data['odds'],
                            'score'         => $data['score'],
                            'stake'         => $data['stake'],
                            'profit_loss'   => $data['pl'],
                            'bet_id'        => $data['bet_id'],
                            'reason'        => $data['reason']
                        ]
                    ];

                    $data['payload'] = serialize(json_encode($payload));
                    DB::beginTransaction();
                    if (AdminSettlement::create($data)) {
                        if (!in_array(env('APP_ENV'), ['local', 'testing'])) {
                           KafkaPush::dispatch('SCRAPING-SETTLEMENTS', $payload, $requestId);
                        }
                        $message = 'success';
                    }
                    DB::commit();                    
                }
                return response()->json([
                    'status'      => true,
                    'status_code' => 200,
                    'data'        => $message
                ], 200);
            }            
        }  
        catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'      => false,
                'status_code' => 500,
                'errors'     => $e->getMessage()
            ], 500);
        }
    }


}
