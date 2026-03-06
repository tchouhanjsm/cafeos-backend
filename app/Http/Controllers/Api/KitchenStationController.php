<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KitchenStation;

class KitchenStationController extends Controller
{

    public function index()
    {
        $stations = KitchenStation::where('is_active',1)->get();

        return response()->json([
            'success'=>true,
            'data'=>$stations
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name'=>'required|string'
        ]);

        $station = KitchenStation::create([
            'name'=>$request->name,
            'code'=>$request->code
        ]);

        return response()->json([
            'success'=>true,
            'data'=>$station
        ]);
    }

}