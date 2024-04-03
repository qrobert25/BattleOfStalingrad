<?php

namespace App\Services;

use BlackScorp\Astar\Grid;
use BlackScorp\Astar\Astar;

class Simulation {
    public function simulate($gameSession = array())
    {
        $results = array(
            'game_session' => array(
                'id' => $gameSession['id'],
                'map_id' => $gameSession['map_id'],
                'map_name' => $gameSession['map_name'],
                'map_size' => $gameSession['map_size'],
            ),
            'players' => array(),
            'results' => array(),
            'errors' => array(),
            'score' => array(),
        );

        // Create a game map
        $obstacles = $this->generateMapObstacles($gameSession['map_size']);
        $difficultLand = $this->generateMapObstacles($gameSession['map_size'], 6);
        $map = $this->generateMap($gameSession['map_size'], $obstacles, $difficultLand);

        $player1 = array(
            'id' => $gameSession['player1']['id'],
            'player' => 'Player 1',
            'name' => $gameSession['player1']['name'],
            'tank_name' => $gameSession['player1']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array(0, (count($map[0]) - 1)),
            'tank_attributes' => $gameSession['player1']['attributes'],
        );

        $player2 = array(
            'id' => $gameSession['player2']['id'],
            'player' => 'Player 1',
            'name' => $gameSession['player2']['name'],
            'tank_name' => $gameSession['player2']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array((count($map) - 1), 0),
            'tank_attributes' => $gameSession['player2']['attributes'],
        );

        // Check if in range
        $tank1 = new GameTank($player1['name'], $gameSession['player1']['tank_name'], $gameSession['player1']['attributes'], $player1['tank_position']);
        $tank2 = new GameTank($player2['name'], $gameSession['player2']['tank_name'], $gameSession['player2']['attributes'], $player2['tank_position']);

        $movements = 0;
        $turnPlayer = 1;
        while ($tank1->getHealth() > 0 && $tank2->getHealth() > 0) {
            if ($turnPlayer == 1) {
                $shooter = &$tank1;
                $target = &$tank2;
                $turnPlayer++;
            } else {
                $shooter = &$tank2;
                $target = &$tank1;
                $turnPlayer--;
            }

            if (!$shooter->checkShootingRange($target, $map)) {
                $result = $this->moveTank($shooter, $target, $map);
                
                // If false, the tanks cannot reach each other, so the game is over.
                if ($result['success'] === false) {
                    array_push($results['errors'], "Game Over: ".$shooter->getName()." cannot reach ".$target->getName());
                    break;
               }
            }

            $shooter->tryShooting($target, $map);

            if ($target->getHealth() <= 0) {
                break;
            }

            if ($movements >= 100) {
                array_push($results['errors'], "Game Over: Maximum number of movements reached");
                break;
            }

            $movements++;
        }

        if (count($results['errors']) == 0) {
            $winner = $tank1->getHealth() > 0 ? $tank1 : $tank2;
            $loser = $tank1->getHealth() > 0 ? $tank2 : $tank1;

            array_push($results['results'], $winner->getName()." (".$winner->getPlayerName().") has won the game");
            array_push($results['results'], $loser->getName()." (".$loser->getPlayerName().") has lost the game");
        }

        $score = $this->sendScores($gameSession, $tank1, $tank2);

        $results['score'] = $score;
        $results['score']['players'] = [
            'player_1' => [
                'id' => $gameSession['player1']['id'],
                'score' => $tank1->getScore(),
            ],
            'player_2' => [
                'id' => $gameSession['player2']['id'],
                'score' => $tank2->getScore(),
            ]
        ];

        $results['players'] = array($player1, $player2);

        return $results;
    }

    public function moveTank(&$shooter, &$target, $map)
    {
        $return = array('success' => true);
        
        $shooterPosition = $shooter->getPosition();
        $targetPosition = $target->getPosition();

        $i = 0;
        $start = [];
        $end = [];
        while (empty($end) && $i < 10) {
            $grid = new Grid($map);
            $astar = new Astar($grid);

            // 9 number is the obstacle
            $astar->blocked([9]);

            $start = $grid->getPoint($shooterPosition[0], $shooterPosition[1]);
            $end = $grid->getPoint($targetPosition[0], $targetPosition[1]);
            $i++;
        }

        $steps = $astar->search($start, $end);

        if (count($steps) === 0){
            // echo "<p>".$shooter->getName()." cannot reach ".$target->getName()."</p>";
            $return['success'] = false;
        } else {
            $previousPosition = $shooterPosition;
            $fuelRange =  $shooter->getFuelRange();
            foreach($steps as $step){
                $score = $step->getScore();

                if ($score == 0) {
                    continue;
                }

                $cost = $step->getCosts();
                $fuelRange -= $cost;

                // In this case, the tank has reached the target position.
                // We must prevent the tank from moving beyond the target position
                if ($targetPosition == [$step->getY(), $step->getX()]) {
                    // echo "<p>".$shooter->getName()." has reached the target position</p>";
                    $shooter->setPosition([$previousPosition[0], $previousPosition[1]]);
                    break;
                }
                
                // If the tank has run out of fuel, it must stop moving and
                if ($fuelRange <= 0) {
                    if ($map[$step->getY()][$step->getX()] == 9) {
                        // echo "<p>".$shooter->getName()." has reached an obstacle and has run out of fuel</p>";
                    }                        
                    $shooter->setPosition([$step->getY(), $step->getX()]);
                    break;
                }

                $previousPosition = [$step->getY(), $step->getX()];
            }
        }

        return $return;
    }

    public function sendScores($gameSession, $tank1, $tank2)
    {
        $data = [
            'map_id' => $gameSession['map_id'],
            'game_session_id' => $gameSession['id'],
            'player_1' => json_encode([
                'id' => $gameSession['player1']['id'],
                'score' => $tank1->getScore(),
            ]),
            'player_2' => json_encode([
                'id' => $gameSession['player2']['id'],
                'score' => $tank2->getScore(),
            ]),
        ];

        $scoresService = new \App\Services\BattleScores();
        $result = $scoresService->save($data);

        return $result;
    }

    public function generateMap($size = array(), $obstacles = array(), $difficultLand = array())
    {
        $rawMap = array();

        for ($i = 0; $i < $size[0]; $i++) {
            $rawMap[$i] = array();
            for ($j = 0; $j < $size[1]; $j++) {
                $rawMap[$i][$j] = 1;
            }
        }

        foreach ($obstacles as $obstacle) {
            $rawMap[$obstacle[0]][$obstacle[1]] = 9;
        }

        foreach ($difficultLand as $cell) {
            $rawMap[$cell[0]][$cell[1]] = 2;
        }

        return $rawMap;
    }

    public function generateMapObstacles($dimensions = array(50, 50), $times = 1)
    {
        $obstacles = array();

        $map = array();
        for ($i=0; $i < $dimensions[0]; $i++) {
            for ($j=0; $j < $dimensions[1]; $j++) {
                $map[$i][$j] = 0;    
            }
        }

        // Build a rectangle shape obstacles
        // Per each 50 of dimensions [0], we will have 4 obstacles
        $obstaclesQty = floor($dimensions[0] / 50 * 8 * $times);
        for ($i=0; $i < $obstaclesQty; $i++) {
            $obstacles = array_merge($obstacles, $this->generateRectangleObstacle($map));
        }

        return $obstacles;
    }

    public function generateRectangleObstacle($map, $dimensions = array(5, 5))
    {
        $obstacles = array();

        $mapHeight = count($map);
        $mapWidth = count($map[0]);

        $x = rand(1, ($mapHeight - 1) - $dimensions[0]);
        $y = rand(0, $mapWidth - $dimensions[1]);

        for ($i=0; $i < $dimensions[0]; $i++) {
            for ($j=0; $j < $dimensions[1]; $j++) {
                $newX = ($x + $i) >= $mapHeight ? ($mapHeight - 1) : $x + $i;
                $newY = ($y + $j) >= $mapWidth ? ($mapWidth - 1) : $y + $j;

                $map[$newX][$newY] = 1;
                $obstacles[] = [$newX, $newY];
            }
        }

        return $obstacles;
    }
}