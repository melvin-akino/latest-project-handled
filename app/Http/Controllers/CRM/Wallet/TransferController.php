<?php

namespace App\Http\Controllers\CRM\Wallet;

use App\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\TransferRequest;
use App\Models\CRM\CrmTransfer;
use App\Models\{Currency, Source, UserWallet};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};

class TransferController extends Controller
{

    public function index(Request $request)
    {
        if(!in_array($request->type, ['Deposit', 'Withdraw']))  $request->type = 'Deposit';

        $data['wallet_menu']                        = true;
        $data[strtolower($request->type).'_menu']   = true;
        $data['page_title']                         = $request->type;
        $data['in_app_currencies']                  = Currency::all();
        $data['page_title']                         = $request->type;
        $data['page_description']                   = "Wallet Transaction";

        return view('CRM.wallet.transfer.index')->with($data);
    }

    public function dataTable(Request $request)
    {
        return dataTable($request,  User::leftjoin('wallet','users.id','=','wallet.user_id')->leftjoin('currency','users.currency_id','currency.id')->select('users.id as userid','users.firstname','users.lastname','users.email', 'currency.code','wallet.balance','users.currency_id'), ['firstname', 'lastname', 'email']);

    }

    public function transfer(TransferRequest $request)
    {

        $sender = Auth::guard('crm')->user();
        $mode   = $request->mode;
        $amount = $request->transfer_amount;

        try {
            $receiver = User::findOrFail($request->user_id);
            $currency = Currency::findOrFail($request->currency_id);
        } catch (\Exception $e) {
            return response()->json(swal(
                trans('swal.exception.title'),
                $e->getMessage(),
                trans('swal.exception.type')
            ));
        }

        if($mode == 'add') {
            $transfer_amount = $request->transfer_amount;
            $charge_type     = UserWallet::TYPE_CHARGE;
            $mode_title      = 'Deposit';
            $mode_text       = 'added';
            $source          = Source::where('source_name', 'DEPOSIT')->first();
        } else {
            $transfer_amount = -$request->transfer_amount;
            $charge_type     = UserWallet::TYPE_DISCHARGE;
            $mode_title      = 'Withdraw';
            $mode_text       = 'deducted';
            $source          = Source::where('source_name', 'WITHDRAW')->first();
        }


        try {
            DB::beginTransaction();

            $crm_transfer = CrmTransfer::create([
                'transfer_amount' => $transfer_amount,
                'currency_id'     => $currency->id,
                'crm_user_id'     => $sender->id,
                'reason'          => $request->reason,
                'user_id'         => $receiver->id
            ]);

            $ledger = UserWallet::makeTransaction($receiver->id, $request->transfer_amount, $currency->id, $source->id, $charge_type);
            $crm_transfer_update = CrmTransfer::find($crm_transfer->id);
            $crm_transfer_update->wallet_ledger_id = $ledger->id;
            $crm_transfer_update->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
                return response()->json([
                    "errors" => [
                        "transfer_amount" => ["Wallet Transcation error." . $e->getMessage()]
                    ]
                ], 400);
        }

        $amount =  number_format($request->transfer_amount, 2) . ' ' . $currency->code;

        return response()->json(swal(
            trans('swal.transfer.success.title', [
                'mode_title' => $mode_title
            ]),
            trans('swal.transfer.success.html', [
                'mode'   => $mode_text,
                'amount' => $amount,
                'user'   => $receiver->email
            ]),
            trans('swal.transfer.success.type')
        ));
    }
}
