<?php

namespace App\Http\Controllers\CRM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\SportRequest;
use App\Models\Sport;
use Exception;

class SportsController extends Controller
{
    public function index() 
    {
        $data = [
            'page_title'       => "Sports",
            'page_description' => "Lists all sports",
            'dashboard_menu'   => true,
        ];
        return view('CRM.sports.index')->with($data);
    }

    public function list()
    {
        $sports = Sport::getAllSports();
        foreach ($sports as $sport) {
            $data['data'][] = [
                'id'                => $sport['id'],
                'sport'             => $sport['sport'],
                'details'           => $sport['details'],
                'priority'          => $sport['priority'],
                'is_enabled'        => $sport['is_enabled']
            ];
        }
        return response()->json(!empty($data) ? $data : []);
    }

    public function manage(SportRequest $request) 
    {
        try {
            
            if (!empty($request)) {
                DB::beginTransaction();
                $data = $request->toArray();
                
                if (!empty($data['sportId'])) {
                    $sport = Sport::where('id', $data['sportId'])->first();
                    $sport->id = $data['sportId'];
                    !empty($data['sport']) ? $sport->sport = $data['sport'] : null;
                    !empty($data['details']) ? $sport->details = $data['details'] : null;
                    !empty($data['is_enabled']) ? $sport->is_enabled = $data['is_enabled'] : null;
                    
                    if ($sport->update($data)) {
                        $message = 'success';
                    }
                }
                else {
                    //get the latest priority
                    $latest = Sport::getLatestPriority();
                    $data['slug'] = Str::slug($data['sport']);
                    $data['priority'] = $latest->priority + 1;
                    $data['icon'] = 'sports_'.Str::slug($data['sport']);

                    if (Sport::create($data)) {
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

    public function prioritize(Request $request)
    {
        $sports = Sport::all();

        foreach ($sports as $sport) {
            foreach ($request->order as $order) {
                if ($order['id'] == $sport->id) {
                    $sport->update(['priority' => $order['position']]);
                }
            }
        }
        return response()->json([
            'status'      => true,
            'status_code' => 200,
            'data'        => 'success'
        ], 200);
    }
}
