<?php

namespace App\Services;

use App\Helpers\DB;
use Ramsey\Uuid\Uuid;

class Tanks
{
    private $db;
    private $dbCollection = 'tanks';

    public function __construct()
    {
        $this->db = new DB($this->dbCollection);
    }

    public function save(array $data = [])
    {
        $document = Uuid::uuid4();
        $document = $document->toString();

        $data = [
            'name' => $data['name'],
            'attributes' => $data['attributes'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->insert($document, $data);
        return $result;
    }

    public function getTank($id)
    {
        $tank = $this->db->getDocument($id);
        return $tank;
    }

    public function getTanks()
    {
        $tanks = $this->db->getDocuments();
        return $tanks;
    }

    /**
     * removeAll
     * Removes all tanks. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbCollectionInstance();
        $query = 'DELETE FROM ' . $instance . ' WHERE 1 = 1';
        $this->db->query($query);
    }
}