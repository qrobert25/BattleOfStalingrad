<?php
namespace App\Services;

use App\Helpers\DB;
use Ramsey\Uuid\Uuid;

class Players
{
    private $db;
    private $dbCollection = 'players';

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
        $document = $document->toString();

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
        $instance = $this->db->getDbCollectionInstance();
        $this->db->query('DELETE FROM ' . $instance . ' WHERE 1 = 1');
    }
}