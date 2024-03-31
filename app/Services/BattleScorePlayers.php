<?php

namespace App\Services;

use App\Helpers\DB;
use Ramsey\Uuid\Uuid;

class BattleScorePlayers
{
    private $db;
    private $dbCollection = 'battle_score_players';

    public function __construct()
    {
        $this->db = new DB($this->dbCollection);
    }

    public function getDbCollectionName()
    {
        return $this->dbCollection;
    }

    public function save(array $data = [])
    {
        $document = Uuid::uuid4();

        $data = [
            'battle_score_id' => $data['battle_score_id'],
            'player_id' => $data['player_id'],
            'score' => $data['score'],
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->db->insert($document, $data);
        return $result;
    }

    public function getPlayerScore($id)
    {
        $score = $this->db->getDocument($id);
        return $score;
    }

    public function getPlayerScores()
    {
        $scores = $this->db->getDocuments();
        return $scores;
    }

    /**
     * removeAll
     * Removes all scores. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbCollectionInstance();
        $this->db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');
    }
}