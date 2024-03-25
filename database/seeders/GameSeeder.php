<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // SAVE DEFAULT MAPS
        $maps = new \App\Services\Maps();
        $existingMap = $maps->getMaps();

        if (count($existingMap) > 0) {
            $maps->removeAll();
        }

        $obstacles = array();
        usleep(2);

        for ($i=0; $i <= 49; $i++) {
            if ($i >= 13 && $i <= 43) {
                for ($j=0; $j <= 49; $j++) {
                    // left map side obstacles
                    if ($i <=28 && $j <= $i - 14) {
                        $obstacles[] = [$i, $j];
                    }

                    if ($i > 28 && $j <= 49 - $i) {
                        $obstacles[] = [$i, $j];
                    }

                    // right map side obstacles
                    if ($i <= 25 && $j >= 49 - $i) {
                        $obstacles[] = [$i, $j];
                    }

                    if ($i > 25 && $j >= $i - 4) {
                        $obstacles[] = [$i, $j];
                    }
                }
            }
        }

        $data = [
            'name' => 'Map 1',
            'description' => 'This is a map description for map 1',
            'obstacles' => json_encode($obstacles),
        ];
        $maps = new \App\Services\Maps();
        $maps->save($data);

        // To avoid duplicate timestamps (since the loop is too fast)
        sleep(0.3);

        // SAVE DEFAULT TANKS
        $tanks = new \App\Services\Tanks();
        $tanks->removeAll();

        // To avoid duplicate timestamps (since the loop is too fast)
        sleep(1);

        $data = [
            'name' => 'German Panzer IV',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $tanks->save($data);
        
        // To avoid duplicate timestamps (since the loop is too fast)
        sleep(1);

        $data = [
            'name' => 'Soviet T-34',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $tanks->save($data);

        // SAVE DEFAULT PLAYERS
        $players = new \App\Services\Players();
        $players->removeAll();
        usleep(1);

        for ($i=0; $i < 50; $i++) {
            // To avoid duplicate timestamps (since the loop is too fast)
            usleep(900);
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
}