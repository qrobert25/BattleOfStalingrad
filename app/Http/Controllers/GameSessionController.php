<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    public function createGameSession()
    {
        $gameSessionService = new \App\Services\GameSession();
        $gameSession = $gameSessionService->createGameSession();

        return view('game', compact('gameSession'));
    }
}
