<?php

namespace App\Services;

use App\Helpers\DB;

class GameSession
{
    private $db;
    private $dbCollection = 'game_sessions';
    private $dbSessionPlayersCollection = 'session_players';


    public function __construct()
    {
        $this->db = new DB($this->dbCollection);
    }

    public function createGameSession(array $data = [])
    {
        // Get the maps
        $mapsService = new \App\Services\Maps();
        $maps = $mapsService->getMaps();

        // Select a random map
        $map = $maps[array_rand($maps)];

        // Create the game session
        $result = $this->db->countDocuments();
        $sessionDocument = ($result + 1);
        $data = [
            'map_id' => $map['id'],
            'winning_player_id' => null,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $gameSession = $this->db->insert($sessionDocument, $data);

        // Get the tanks
        $tanksService = new \App\Services\Tanks();
        $tanks = $tanksService->getTanks();

        // Set the players
        $playersService = new \App\Services\Players();
        $players = $playersService->getPlayers();

        // Select random player 1
        $player1 = $players[array_rand($players)];

        // Remove player 1 from the list
        unset($players[$player1['id']]);
        $players = array_values($players);

        // Select random player 2
        $player2 = $players[array_rand($players)];

        $this->addSessionPlayer([
            'game_session_id' => $gameSession['id'],
            'player_id' => $player1['id'],
            $tanks[0]['id'],
        ]);

        $this->addSessionPlayer([
            'game_session_id' => $gameSession['id'],
            'player_id' => $player2['id'],
            $tanks[1]['id'],
        ]);

        $return = [
            'id' => $gameSession['id'],
            'map_id' => $map['id'],
            'map_name' => $map['maps']['name'],
            'map_obstacles' => $map['maps']['obstacles'],
            'player1' => array(
                'id' => $player1['id'],
                'name' => $player1['players']['name'],
                'tank_id' => $tanks[0]['id'],
                'tank_name' => $tanks[0]['tanks']['name']
            ),
            'player2' => array(
                'id' => $player2['id'],
                'name' => $player2['players']['name'],
                'tank_id' => $tanks[1]['id'],
                'tank_name' => $tanks[1]['tanks']['name']
            ),
        ];

        return $return;
    }

    public function addSessionPlayer(array $data = [])
    {
        $db = new DB($this->dbSessionPlayersCollection);
        $result = $db->countDocuments();
        $sessionPlayerDocument = ($result + 1);
        $data = [
            'game_session_id' => $data['game_session_id'],
            'player_id' => $data['player_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $db->insert($sessionPlayerDocument, $data);
    }

    /**
     * removeAll
     * Removes all game sessions. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbInstance();
        $this->db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');

        $db = new DB($this->dbSessionPlayersCollection);
        $instance = $db->getDbInstance();
        $db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');
    }
}