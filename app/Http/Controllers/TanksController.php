<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TanksController extends Controller
{
    public function getTank($id)
    {
        $tanks = new \App\Services\Tanks();
        $tank = $tanks->getTank($id);
        return response()->json($tank);
    }

    public function getTanks()
    {
        $tanks = new \App\Services\Tanks();
        $tanks = $tanks->getTanks();
        return response()->json($tanks);
    }
}
