<?php

namespace App\Services;

use App\Helpers\DB;

class Maps
{
    private $db;
    private $dbCollection = 'maps';

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
            'description' => $data['description'],
            'obstacles' => $data['obstacles'],
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
        $instance = $this->db->getDbInstance();
        $query = 'DELETE FROM ' . $instance . ' WHERE 1 = 1';
        $this->db->query($query);
    }
}
