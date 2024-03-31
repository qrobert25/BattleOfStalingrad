<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle of Stalingrad</title>
</head>
<body>
    <?php
        echo "<h1>Battle of Stalingrad</h1>";

        echo "<h2>Game Session</h2>";
        echo "<p>Game ID: " . $gameSession['id'] . "</p>";
        echo "<p>Map: " . $gameSession['map_name'] . "</p>";
        echo "<br>";

        $player1 = array(
            'id' => $gameSession['player1']['id'],
            'name' => $gameSession['player1']['name'],
            'tank_name' => $gameSession['player1']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array(0, 49),
        );

        echo "<p><strong>Player 1:</strong></p>";
        echo "<ul>";
        echo "<li>Name: " . $gameSession['player1']['name'] . "</li>";
        echo "<li>Tank: " . $gameSession['player1']['tank_name'] . "</li>";
        echo "<li>Initial position: " . json_encode($player1['tank_position']) . "</li>";
        echo "</ul>";

        $player2 = array(
            'id' => $gameSession['player2']['id'],
            'name' => $gameSession['player2']['name'],
            'tank_name' => $gameSession['player2']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array(49, 0),
        );

        echo "<p><strong>Player 2:</strong></p>";
        echo "<ul>";
        echo "<li>Name: " . $gameSession['player2']['name'] . "</li>";
        echo "<li>Tank: " . $gameSession['player2']['tank_name'] . "</li>";
        echo "<li>Initial position: " . json_encode($player2['tank_position']) . "</li>";
        echo "</ul>";

        echo "<h4>Let's battle</h4>";

        $map = generateMap($gameSession['map_size'], $gameSession['map_obstacles']);
        
        foreach ($map as $row) {
            echo json_encode($row)."</br>";
        }

        $tank1 = new GameTank($gameSession['player1']['tank_name'], $gameSession['player1']['attributes'], $player1['tank_position']);
        $tank2 = new GameTank($gameSession['player2']['tank_name'], $gameSession['player2']['attributes'], $player2['tank_position']);

        // $tank1->move('down', 10, $map);
        // $tank1->move('down', 10, $map);


        // $tank1->move('right', 10, $map);
        // $tank1->move('right', 10, $map);
        // $tank1->move('up', 10, $map);

        // $tank1->shoot($tank2, 10, $map);
        // $tank1->shoot($tank2, 10, $map);
        // $tank1->shoot($tank2, 10, $map);

        //sendScores($gameSession);

        function sendScores($gameSession)
        {
            try {
                $url = 'http://localhost:80/api/v1/scores/';

                $data = [
                    'map_id' => $gameSession['map_id'],
                    'game_session_id' => $gameSession['id'],
                    'player_1' => json_encode([
                        'id' => $gameSession['player1']['id'],
                        'score' => 1000,
                    ]),
                    'player_2' => json_encode([
                        'id' => $gameSession['player2']['id'],
                        'score' => 500,
                    ]),
                ];

                $options = [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($data),
                    CURLOPT_RETURNTRANSFER => true,
                ];

                $curl = curl_init();
                curl_setopt_array($curl, $options);
                $response = curl_exec($curl);
                curl_close($curl);

                if ($response === false) {
                    echo "<p>Failed to send scores to the API #1</p>";
                    echo "<p>Error: " . curl_error($curl) . "</p>";
                } else {
                    echo "<p>Scores sent successfully: ".json_encode($response)."</p>";
                }


                // Handle the response from the API
                // ...
            } catch (\Exception $e) {
                echo "<p>Failed to send scores to the API</p>";
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
            
        }

        function generateMap($size = array(), $obstacles = array())
        {
            $rawMap = array();

            for ($i = 0; $i < $size[0]; $i++) {
                $rawMap[$i] = array();
                for ($j = 0; $j < $size[1]; $j++) {
                    $rawMap[$i][$j] = 0;
                }
            }

            foreach ($obstacles as $obstacle) {
                $rawMap[$obstacle[0]][$obstacle[1]] = 1;
            }

            return $rawMap;
        }

        class GameTank
        {
            private $name;
            private $health = 100;
            private $armor = 0;
            private $damage = 20;
            private $fuelRange = 6;
            private $fireRange = 3;
            private $position = array(0, 0);

            public function __construct(String $name, $attributes = array(), $position = array(0, 0))
            {
                $this->name = $name;
                $this->position = $position;

                if (!empty($attributes['armor']) && is_int($attributes['armor'])) {
                    $this->armor = $attributes['armor'];
                }

                if (!empty($attributes['damage']) && is_int($attributes['damage'])) {
                    $this->damage = $attributes['damage'];
                }

                if (!empty($attributes['fuel_range']) && is_int($attributes['fuel_range'])) {
                    $this->fuelRange = $attributes['fuel_range'];
                }

                if (!empty($attributes['fire_range']) && is_int($attributes['fire_range'])) {
                    $this->fireRange = $attributes['fire_range'];
                }
            }

            public function move($direction, $steps = 1, $map)
            {
                if ($steps > $this->fuelRange) {
                    $steps = $this->fuelRange;
                }

                while ($steps > 0) {
                    $newPosition = $this->position;

                    switch ($direction) {
                        case 'up':
                            $newPosition[0] -= 1;
                            break;
                        case 'down':
                            $newPosition[0] += 1;
                            break;
                        case 'left':
                            $newPosition[1] -= 1;
                            break;
                        case 'right':
                            $newPosition[1] += 1;
                            break;
                    }

                    if ($newPosition[0] < 0 || $newPosition[0] > count($map) || $newPosition[1] < 0 || $newPosition[1] > count($map[0])) {
                        echo "<p>".$this->name." cannot move to the specified position</p>";
                        break;
                    }
    
                    if ($map[$newPosition[0]][$newPosition[1]] == 1) {
                        echo "<p>".$this->name." has encountered an obstacle. The current position is ".json_encode($this->position)."</p>";
                        break;
                    }

                    $this->position = $newPosition;

                    if ($steps == 1) {
                        echo "<p>".$this->name." moved to position ".json_encode($this->position)."</p>";
                    }

                    $steps--;
                }
            }

           public function shoot(&$target, $steps = 1, $map)
            {
                // Check if the target has already been defeated
                if ($target->health <= 0) {
                    //echo "<p>".$target->name." has already been defeated.</p>";
                    return;
                }

                // Checking fire range limit
                if ($steps > $this->fireRange) {
                    $steps = $this->fireRange;
                }

                // Determine the direction of the target
                $direction = '';

                if ($this->position[0] == $target->position[0]) {
                    if ($this->position[1] < $target->position[1]) {
                        $direction = 'right';
                    } else {
                        $direction = 'left';
                    }
                }

                if ($this->position[1] == $target->position[1]) {
                    if ($this->position[0] < $target->position[0]) {
                        $direction = 'down';
                    } else {
                        $direction = 'up';
                    }
                }

                // Calculate the path of the shot to be taken
                $path = $this->position;

                // Move the shot along the path
                while ($steps > 0) {
                    switch ($direction) {
                        case 'up':
                            $path[0] -= 1;
                            break;
                        case 'down':
                            $path[0] += 1;
                            break;
                        case 'left':
                            $path[1] -= 1;
                            break;
                        case 'right':
                            $path[1] += 1;
                            break;
                    }

                    if ($path[0] < 0 || $path[0] >= count($map) || $path[1] < 0 || $path[1] >= count($map[0])) {
                        echo "<p>".$this->name." shot went out of the map.</p>";
                        echo "<p>1#: ".($path[0] < 0)."</p>";
                        break;
                    }
    
                    if ($map[$path[0]][$path[1]] == 1) {
                        echo "<p>".$this->name." shot has encountered an obstacle.</p>";
                        break;
                    }

                    if ($path == $target->position) {
                        $armorReduction = 0;
                        $damage = $this->damage;

                        if ($target->armor > 0) {
                            $armorReduction = -10;
                            $damage -= 10;
                            $target->armor -= 10;
                        }

                        $target->health -= $damage;

                        echo "<p>".$this->name." shot ".$target->name." and caused ".$damage." damage.";
                        
                        if ($armorReduction < 0) {
                            echo "<br> The armor of ".$target->name." has been reduced by ".$armorReduction;
                            
                            if ($target->armor == 0) {
                                echo " and it has been depleted";
                            }

                            echo ".";
                        }

                        echo "<br>".$target->name." health is now ".$target->health.".</p>";

                        echo "</p>";

                        if ($target->health <= 0) {
                            echo "<p>".$target->name." has been defeated.</p>";
                            break;
                        }

                        return;
                    }

                    if ($steps == 1) {
                        echo "<p>".$this->name." shot did not reach the target position</p>";
                    }

                    $steps--;
                }
            }

            public function getPosition()
            {
                return $this->position;
            }

            public function getName()
            {
                return $this->name;
            }
        }
    ?>
</body>
</html>