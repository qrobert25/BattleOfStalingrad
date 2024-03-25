<?php
namespace App\Services;

use App\Helpers\DB;

class Players
{
    private $db;
    private $dbCollection = 'players';

    public function __construct()
    {
        $this->db = new DB($this->dbCollection);
    }

    public function save(array $data = [])
    {
        $result = $this->db->countDocuments();
        $document = ($result + 1);
        $data = [
            'name' => $data['name'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->insert($document, $data);
        return $result;
    }

    public function getPlayer($id)
    {
        $player = $this->db->getDocument($id);
        return $player;
    }

    public function getPlayers()
    {
        $players = $this->db->getDocuments();
        return $players;
    }

    /**
     * removeAll
     * Removes all players. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbInstance();
        $this->db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');
    }
}