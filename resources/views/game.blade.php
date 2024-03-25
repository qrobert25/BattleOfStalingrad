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

        $map = generateMapTemplate();
        $map = loadObstacles(json_decode($gameSession['map_obstacles'], true), $map);

        $player1['tank_position'] = array(0, 21);
        echo "<p><strong>".$player1['name']."</strong> moved to position ".json_encode($player1['tank_position'])."</p>";
        shot($player1, $player2, 'down', $map);


        $player2['tank_position'] = array(49, 21);
        echo "<p><strong>".$player2['name']."</strong> moved to position ".json_encode($player2['tank_position'])."</p>";
        shot($player2, $player1, 'up', $map);

        $player1['tank_position'] = array(30, 21);
        echo "<p><strong>".$player1['name']."</strong> moved to position ".json_encode($player1['tank_position'])."</p>";
        shot($player1, $player2, 'down', $map);

        shot($player2, $player1, 'up', $map);

        function generateMapTemplate()
        {
            $rawMap = array();

            for ($i = 0; $i < 50; $i++) {
                $rawMap[$i] = array();
                for ($j = 0; $j < 50; $j++) {
                    $rawMap[$i][$j] = 'x';
                }
            }

            return $rawMap;
        }

        function loadObstacles($obstacles, $map)
        {
            
            foreach ($obstacles as $obstacle) {
                $map[$obstacle[0]][$obstacle[1]] = 'o';
            }

            return $map;
        }

        function shot(&$shooter, &$target, $direction, $map)
        {
            $obstacle = false;
            $success = false;

            switch ($direction) {
                case 'up':
                    for ($j=$shooter['tank_position'][0]; $j >= $target['tank_position'][0]; $j--) { 
                        if ($map[$j][$shooter['tank_position'][1]] == 'o') {
                            $obstacle = true;
                            break;
                        }

                        if ($shooter['tank_position'][1] == $target['tank_position'][1] && $j == $target['tank_position'][0]) {
                            $success = true;
                            break;
                        }
                    }
                    break;
                case 'down':
                    for ($j=$shooter['tank_position'][0]; $j <= $target['tank_position'][0]; $j++) { 
                        if ($map[$j][$shooter['tank_position'][1]] == 'o') {
                            $obstacle = true;
                            break;
                        }

                        if ($shooter['tank_position'][1] == $target['tank_position'][1] && $j == $target['tank_position'][0]) {
                            $success = true;
                            break;
                        }
                    }
                    break;
            }

            if ($success) {
                $shooter['tank_health'] -= 50;
                echo "<p><strong>".$shooter['name']."</strong> shot <strong>".$target['name']."</strong> and caused 50 damage</p>";

                if ($shooter['tank_health'] <= 0) {
                    // Send a POST form request to the API to update the game session status
                    echo "<p><strong>".$shooter['name']."</strong> has been defeated</p>";

                    // End the game


                }
            } else if ($obstacle) {
                echo "<p><strong>".$shooter['name']."</strong> shot an obstacle</p>";
            } else {
                echo "<p><strong>".$shooter['name']."</strong> missed the shot</p>";
            }
        }
    ?>
</body>
</html>