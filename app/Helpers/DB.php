<?php

namespace App\Helpers;

use Couchbase\Cluster;
use Couchbase\ClusterOptions;

class DB
{
    private $host = null;
    private $username = null;
    private $password = null;
    private $cluster = null;
    private $bucket = null;
    private $bucketName = null;
    private $scope = null;
    private $scopeName = null;
    private $collection = null;
    private $collectionName = null;

    public function __construct($collectionName = null)
    {
        // Initialize the connection data
        $this->host = "couchbase://".config('database.connections.couchbase.driver');
        $this->username = config('database.connections.couchbase.username');
        $this->password = config('database.connections.couchbase.password');
        $this->bucketName = config('database.connections.couchbase.database');
        $this->scopeName = 'game';
        $this->collectionName = $collectionName;

        // Initialize the connection to the cluster
        $options = new ClusterOptions();
        $options->credentials($this->username, $this->password);

        // initialize objects to be used in the class
        $this->cluster = new Cluster($this->host, $options);
        $this->bucket = $this->cluster->bucket($this->bucketName);
        $this->scope = $this->bucket->scope($this->scopeName);
        $this->collection = $this->scope->collection($collectionName);
        
        // Next method is creating the scope if it doesn't exist.
        // It can be do it better with more time         
        try {
            $createScopeQuery = "CREATE SCOPE `$this->bucketName`.`$this->scopeName`";
            $this->query($createScopeQuery);
        } catch (\Exception $e) {
            // Scope already exists
        }

       $this->checkCollection($collectionName);
    }

    public function getDbInstance() : String
    {
        return "`{$this->bucketName}`.`{$this->scopeName}`.`{$this->collectionName}`";
    }

    public function checkCollection($collectionName)
    {
        // Next method is creating the collection if it doesn't exist.
        // It can be do it better with more time
        try {
            $query = "CREATE COLLECTION `$this->bucketName`.`$this->scopeName`.`$collectionName`";
            $this->query($query);
        } catch (\Exception $e) {
            // Collection already exists
        }

        try {
            $query = "CREATE PRIMARY INDEX ON `$this->bucketName`.`$this->scopeName`.`$collectionName`";
            $this->query($query);
        } catch (\Exception $e) {
            // Collection already exists
        }
    }

    public function changeCollection($collectionName)
    {
        $this->collection = $this->scope->collection($collectionName);
        $this->checkCollection($collectionName);
    }

    public function insert(String $document, array $data = [])
    {
        // Insert a document
        $this->collection->upsert($document, $data);

        // Get a document
        $result = $this->collection->get($document);
        $result = $result->content();
        $result['id'] = $document;
        return $result;
    }

    public function remove(String $document)
    {
        // Remove a document
        $this->collection->remove($document);
    }

    public function query(String $query) {
        try {
            $result = $this->cluster->query($query);
            $result->rows();
        } catch (\Exception $e) {
            // Something went wrong
            $result = [];
        }

        return $result;
    }

    public function countDocuments()
    {
        $result = 0;
        try {
            $query = "SELECT COUNT(*) AS count FROM `{$this->bucketName}`.`{$this->scopeName}`.`{$this->collectionName}`";
            $result = $this->query($query);

            if (is_array($result)){
                $result = $result[0]['count'];
            } else {
                $result = $result->rows()[0]['count'];
            }

        } catch (\Exception $e) {
            // Something went wrong
            $result = 0;
        }

        return $result;
    }

    public function getDocument($document)
    {
        try{
            $query = "SELECT META().id AS id, * FROM `{$this->bucketName}`.`{$this->scopeName}`.`{$this->collectionName}` WHERE META().id = '$document' LIMIT 1";
            $result = $this->query($query);
        } catch (\Exception $e) {
            $result = [];
        }

        return $result;
    }

    public function getDocuments()
    {
        $query = "SELECT META().id AS id, * FROM `{$this->bucketName}`.`{$this->scopeName}`.`{$this->collectionName}`";
        $result = $this->query($query);
        return $result->rows();
    }
}