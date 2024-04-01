<?php

namespace App\Services;

use App\Helpers\DB;
use Ramsey\Uuid\Uuid;

class Maps
{
    private $db;
    private $dbCollection = 'maps';

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
            'size' => $data['size'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert($document, $data);
        return;
    }

    public function getMap($id)
    {
        $map = $this->db->getDocument($id);
        return $map;
    }

    public function getMaps()
    {
        $maps = $this->db->getDocuments();
        return $maps;
    }

    /**
     * removeAll
     * Removes all maps. It is only used in the seeder.
     */
    public function removeAll()
    {
        $instance = $this->db->getDbCollectionInstance();
        $query = 'DELETE FROM ' . $instance . ' WHERE 1 = 1';
        $this->db->query($query);
    }
}
