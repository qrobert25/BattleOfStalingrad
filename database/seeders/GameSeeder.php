<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // REMOVE ALL BATTLES SCORES
        $scores = new \App\Services\BattleScores();
        $scores->removeAll();
        $scores->deleteLeaderboardCache();

        // REMOVE ALL BATTLE SCORE PLAYERS
        $battleScorePlayers = new \App\Services\BattleScorePlayers();
        $battleScorePlayers->removeAll();
        

        // CREATE A INDEX FOR BATTLE SCORES COLLECTION IF NOT EXISTS
        try {
            $db = new \App\Helpers\DB('battle_scores');
            $db->query('CREATE INDEX idx_map_id ON `battleofstalingrad`.`game`.`battle_scores`(map_id);');
            $db->query('CREATE INDEX idx_battle_score_id ON `battleofstalingrad`.`game`.`battle_score_players`(battle_score_id);');
            $db->query('CREATE INDEX idx_player_id ON `battleofstalingrad`.`game`.`battle_score_players`(player_id);');
        } catch (\Exception $e) {
            // Index already exists
        }

        // SAVE DEFAULT MAPS
        $maps = new \App\Services\Maps();
        $existingMap = $maps->getMaps();

        if (count($existingMap) > 0) {
            $maps->removeAll();
        }

        // map 1
        $size = array(50, 50);
        $obstacles = $this->generateMapObstacles($size);

        $data = [
            'name' => 'Map 1',
            'size' => $size,
            'obstacles' => $obstacles,
        ];

        $maps = new \App\Services\Maps();
        $maps->save($data);

        // map 2
        $size = array(75, 75);
        $obstacles = $this->generateMapObstacles($size);

        $data = [
            'name' => 'Map 2',
            'size' => $size,
            'obstacles' => $obstacles,
        ];

        $maps = new \App\Services\Maps();
        $maps->save($data);

        // map 3
        $size = array(100, 100);
        $obstacles = $this->generateMapObstacles($size);

        $data = [
            'name' => 'Map 3',
            'size' => $size,
            'obstacles' => $obstacles,
        ];

        $maps = new \App\Services\Maps();
        $maps->save($data);

        // SAVE DEFAULT TANKS
        $tanks = new \App\Services\Tanks();
        $tanks->removeAll();

        $data = [
            'name' => 'German Panzer IV',
            'attributes' => [
                'armor' => 50,
                'damage' => 50,
                'fuel_range' => 4,
                'fire_range' => 6,
            ],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $tanks->save($data);
        
        $data = [
            'name' => 'Soviet T-34',
            'attributes' => [
                'armor' => 30,
                'damage' => 40,
                'fuel_range' => 6,
                'fire_range' => 4,
            ],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $tanks->save($data);

        // SAVE DEFAULT PLAYERS
        $players = new \App\Services\Players();
        $players->removeAll();

        for ($i=0; $i < 50; $i++) {
            $players = new \App\Services\Players();
            $players->save([
                'name' => 'Player_' . ($i + 1),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // RESET GAME SESSIONS & SESSION PLAYERS
        $gameSession = new \App\Services\GameSession();
        $gameSession->removeAll();
    }

    public function generateMapObstacles($dimensions = array(50, 50)) {
        $obstacles = array();

        $map = array();
        for ($i=0; $i < $dimensions[0]; $i++) {
            for ($j=0; $j < $dimensions[1]; $j++) {
                $map[$i][$j] = 0;    
            }
        }

        // Build a rectangle shape obstacles
        // Per each 50 of dimensions [0], we will have 4 obstacles
        $obstaclesQty = floor($dimensions[0] / 50 * 6);
        for ($i=0; $i < $obstaclesQty; $i++) {
            $obstacles = array_merge($obstacles, $this->generateRectangleObstacle($map));
        }

        return $obstacles;
    }

    public function generateRectangleObstacle($map, $dimensions = array(5, 5)) {
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
