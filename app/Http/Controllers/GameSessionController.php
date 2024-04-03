<?php

namespace App\Http\Controllers;

class GameSessionController extends Controller
{
    public function createGameSession()
    {
        $gameSessionService = new \App\Services\GameSession();
        $gameSession = $gameSessionService->createGameSession();

        $simulationService = new \App\Services\Simulation();
        $simulation = $simulationService->simulate($gameSession);

        return response()->json($simulation);
    }
}
