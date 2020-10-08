<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\AdminSettlementRequest;
use App\Models\{Transaction, AdminSettlement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
        $transactions = Transaction::getTransactions('open');
        dd($transactions);
        return response()->json(!empty($data) ? $data : []);
    }

    public function generate_settlement(AdminSettlementRequest $request) 
    {
        try {
            if (!empty($request)) {
                DB::beginTransaction();
                $data = $request->toArray();

                if (!empty($data)) {
                    if (AdminSettlement::create($data)) {
                        //Generate kafka json payload here

                        //Push payload to kafka

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
