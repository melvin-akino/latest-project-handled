<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\AdminSettlementRequest;
use App\Models\CRM\{Transaction, AdminSettlement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


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
                DB::beginTransaction();
                $data = $request->toArray();

                if (!empty($data)) {
                    preg_match_all('!\d+!', $data['bet_id'], $id);
                    //Generate kafka json payload here
                    $payload = [
                        'request_id'    => Str::uuid(),
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
                    if (AdminSettlement::create($data)) {
                        
                        //Push payload to kafka
                        //json_encode($payload);


                        $message = 'success';
                    }                    
                }

                DB::commit();

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
