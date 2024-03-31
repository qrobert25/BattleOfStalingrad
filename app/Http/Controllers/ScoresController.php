<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScoresController extends Controller
{
    public function saveScore(Request $request)
    {
        $data = $request->all();
        $scoresService = new \App\Services\BattleScores();
        $result = $scoresService->save($data);
        return response()->json($result);
    }

    public function getScore($id)
    {
        $scoresService = new \App\Services\BattleScores();
        $score = $scoresService->getScore($id);

        return response()->json($score);
    }

    public function getLeaderboard($period = '')
    {
        $leaderboard = [];
        $scoresService = new \App\Services\BattleScores();

        // Set the date range based on the period
        switch($period) {
            case 'daily':
                $leaderboard = $scoresService->getDailyLeaderboard();
                break;
            case 'weekly':
                $leaderboard = $scoresService->getWeeklyLeaderboard();
                break;
            case 'monthly':
                $leaderboard = $scoresService->getMonthlyLeaderboard();
                break;
            default:
                $leaderboard = $scoresService->getLeaderboard();
        }
        
        return response()->json($leaderboard);
    }
}