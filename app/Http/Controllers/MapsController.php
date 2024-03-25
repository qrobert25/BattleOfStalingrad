<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MapsController extends Controller
{
    public function getMap($id)
    {
        $maps = new \App\Services\Maps();
        $map = $maps->getMap($id);
        return response()->json($map);
    }
}
