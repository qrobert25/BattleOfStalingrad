<!DOCTYPE html>
<html lang="en"></html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle of Stalingrad</title>
</head>
<body>
    <?php
        // Create a game map
        $obstacles = generateMapObstacles($gameSession['map_size']);
        $difficultLand = generateMapObstacles($gameSession['map_size'], $times = 6);
        $map = generateMap($gameSession['map_size'], $obstacles, $difficultLand);

        $player1 = array(
            'id' => $gameSession['player1']['id'],
            'name' => $gameSession['player1']['name'],
            'tank_name' => $gameSession['player1']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array(0, (count($map[0]) - 1)),
            'tank_attributes' => $gameSession['player1']['attributes'],
        );

        $player2 = array(
            'id' => $gameSession['player2']['id'],
            'name' => $gameSession['player2']['name'],
            'tank_name' => $gameSession['player2']['tank_name'],
            'tank_health' => 100,
            'tank_position' => array((count($map) - 1), 0),
            'tank_attributes' => $gameSession['player2']['attributes'],
        );

        // Check if in range
        $tank1 = new GameTank($player1['name'], $gameSession['player1']['tank_name'], $gameSession['player1']['attributes'], $player1['tank_position']);
        $tank2 = new GameTank($player2['name'], $gameSession['player2']['tank_name'], $gameSession['player2']['attributes'], $player2['tank_position']);

        echo "<h1>Battle of Stalingrad</h1>";

        echo "<h2>Game Session</h2>";
        echo "<p>Game ID: " . $gameSession['id'] . "</p>";
        echo "<p>Map: " . $gameSession['map_name'] . "</p>";
        echo "<br>";

        echo "<p><strong>Player 1:</strong></p>";
        echo "<ul>";
        echo "<li>Name: " . $gameSession['player1']['name'] . "</li>";
        echo "<li>Tank: " . $gameSession['player1']['tank_name'] . "</li>";
        echo "<li>Initial position: " . json_encode($player1['tank_position']) . "</li>";
        echo "<li>Attributes: " . json_encode($player1['tank_attributes']) . "</li>";
        echo "<li>Health: " . $player1['tank_health'] . "</li>";
        echo "</ul>";

        echo "<p><strong>Player 2:</strong></p>";
        echo "<ul>";
        echo "<li>Name: " . $gameSession['player2']['name'] . "</li>";
        echo "<li>Tank: " . $gameSession['player2']['tank_name'] . "</li>";
        echo "<li>Initial position: " . json_encode($player2['tank_position']) . "</li>";
        echo "<li>Attributes: " . json_encode($player2['tank_attributes']) . "</li>";
        echo "<li>Health: " . $player2['tank_health'] . "</li>";
        echo "</ul>";

        echo "<h4>Let's battle</h4>";

        
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
                $result = moveTank($shooter, $target, $map);
                
                // If false, the tanks cannot reach each other, so the game is over.
                if ($result['success'] === false) {
                    echo "<p>Game Over: ".$shooter->getName()." cannot reach ".$target->getName()."</p>";
                   break;
               }
            }

            $shooter->tryShooting($target, $map);

            if ($target->getHealth() <= 0) {
                break;
            }

            if ($movements >= 100) {
                echo "<p>Game Over: Maximum number of movements reached</p>";
                break;
            }

            $movements++;
        }

        echo "<h4>Game Over</h4>";
        sendScores($gameSession, $tank1, $tank2);

        function moveTank(&$shooter, &$target, $map)
        {
            $return = array('success' => true);
            
            $shooterPosition = $shooter->getPosition();
            $targetPosition = $target->getPosition();

            $i = 0;
            $start = [];
            $end = [];
            while (empty($end) && $i < 10) {
                $grid = new BlackScorp\Astar\Grid($map);
                $astar = new BlackScorp\Astar\Astar($grid);

                // 9 number is the obstacle
                $astar->blocked([9]);

                $start = $grid->getPoint($shooterPosition[0], $shooterPosition[1]);
                $end = $grid->getPoint($targetPosition[0], $targetPosition[1]);
                $i++;
            }

            $steps = $astar->search($start, $end);

            if (count($steps) === 0){
                echo "<p>".$shooter->getName()." cannot reach ".$target->getName()."</p>";
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
                        echo "<p>".$shooter->getName()." has reached the target position</p>";
                        $shooter->setPosition([$previousPosition[0], $previousPosition[1]]);
                        break;
                    }
                    
                    // If the tank has run out of fuel, it must stop moving and
                    if ($fuelRange <= 0) {
                        if ($map[$step->getY()][$step->getX()] == 9) {
                            echo "<p>".$shooter->getName()." has reached an obstacle and has run out of fuel</p>";
                        }                        
                        $shooter->setPosition([$step->getY(), $step->getX()]);
                        break;
                    }

                    $previousPosition = [$step->getY(), $step->getX()];
                }
            }

            return $return;
        }

        function sendScores($gameSession, $tank1, $tank2)
        {
            try {
                $url = 'http://localhost:80/api/v1/scores/';

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
                    $id = null;
                    try {
                        $response = json_decode($response);
                        $id = $response->id;
                    } catch (\Exception $e) {
                        //
                    }

                    if (!empty($id)) {
                        echo '<p><a href="/api/v1/scores/'.$id.'" target="_blank">Review score</a></p>';
                    } else {
                        echo "<p>Scores sent successfully: ".json_encode($response)."</p>";
                    }
                }


                // Handle the response from the API
                // ...
            } catch (\Exception $e) {
                echo "<p>Failed to send scores to the API</p>";
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
            
        }

        function generateMap($size = array(), $obstacles = array(), $difficultLand = array())
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

        function generateMapObstacles($dimensions = array(50, 50), $times = 1) {
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
                $obstacles = array_merge($obstacles, generateRectangleObstacle($map));
            }

            return $obstacles;
        }

        function generateRectangleObstacle($map, $dimensions = array(5, 5)) {
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

        class GameTank
        {
            private $playerName;
            private $name;
            private $health = 100;
            private $armor = 0;
            private $damage = 20;
            private $fuelRange = 6;
            private $fireRange = 3;
            private $position = array(0, 0);
            private $score = 0;

            public function __construct(String $playerName, String $name, $attributes = array(), $position = array(0, 0))
            {
                $this->playerName = $playerName;
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

            public function checkShootingRange($target, $map)
            {
                $obstacles = false;
                $response = false;

                $distance = 0;
                $shooterPosition = $this->getPosition();
                $targetPosition = $target->getPosition();

                if ($shooterPosition[0] == $targetPosition[0]) {
                    $distance = abs($shooterPosition[1] - $targetPosition[1]);

                    // Check if there is an obstacle between the shooter and the target
                    foreach (range($shooterPosition[1], $targetPosition[1]) as $i) {
                        if ($map[$shooterPosition[0]][$i] == 9) {
                            $obstacles = true;
                            break;
                        }
                    }
                }

                if ($shooterPosition[1] == $targetPosition[1]) {
                    $distance = abs($shooterPosition[0] - $targetPosition[0]);

                    // Check if there is an obstacle between the shooter and the target
                    foreach (range($shooterPosition[0], $targetPosition[0]) as $i) {
                        if ($map[$i][$shooterPosition[1]] == 9) {
                            $obstacles = true;
                            break;
                        }
                    }
                }

                if (!$obstacles && $distance > 0 && $distance <= $this->fireRange) {
                    $response = true;
                }

                return $response;
            }

            public function tryShooting(&$target, $map)
            {
                // Check if the target is within the fire range
                $inRange = $this->checkShootingRange($target, $map);

                if (!$inRange) {
                    return;
                }
              
                // Check if the target has already been defeated
                if ($target->getHealth() <= 0) {
                    echo "<p>".$target->getName()." has already been defeated.</p>";
                    return;
                }

                $armorReduction = 0;
                $damage = $this->damage;

                if ($target->getArmor() > 0) {
                    $armorReduction = -10;
                    $damage -= 10;
                    $target->setArmor($target->getArmor() - 10);
                }

                $target->setHealth($target->getHealth() - $damage);

                echo "<p>".$this->name." shot ".$target->getName()." and caused ".$damage." damage.";
                $this->updateScore(130);
                
                if ($armorReduction < 0) {
                    echo "<br> The armor of ".$target->getName()." has been reduced by ".$armorReduction;
                    $this->updateScore(150);
                    
                    if ($target->getArmor() == 0) {
                        echo " and it has been depleted";
                        $this->updateScore(180);
                    }

                    echo ".";
                }

                echo "<br>".$target->getName()." health is now ".$target->getHealth().".</p>";
                echo "</p>";

                if ($target->getHealth() <= 0) {
                    echo "<p>".$target->getName()." has been defeated.</p>";
                    echo "<p>".$this->playerName." wins the game.</p>";
                    echo "<p>".$target->getPlayerName()." loses the game.</p>";
                    $this->updateScore(200);
                }
            }

            public function getPlayerName()
            {
                return $this->playerName;
            }

            public function getPosition()
            {
                return $this->position;
            }

            public function setPosition($position)
            {
                $this->position = $position;
                $this->updateScore(10);
                echo "<p>".$this->name." moved to position ".json_encode($this->position)."</p>";
            }

            public function getFuelRange()
            {
                return $this->fuelRange;
            }

            public function getName()
            {
                return $this->name;
            }

            public function getHealth()
            {
                return $this->health;
            }

            public function setHealth($health)
            {
                if ($health < $this->health) {
                    $this->updateScore(-15);
                }

                $this->health = $health;
            }

            public function getArmor()
            {
                return $this->armor;
            }

            public function setArmor($armor)
            {
                $this->armor = $armor;
            }

            public function getScore()
            {
                return $this->score;
            }

            public function updateScore($score)
            {
                $this->score += $score;
            }
        }
    ?>
</body>
</html>