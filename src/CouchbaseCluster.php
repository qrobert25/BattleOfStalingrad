<?php
namespace Src;

use Couchbase\Cluster;
use Couchbase\ClusterOptions;

class CouchbaseCluster
{
    private $host = null;
    private $username = null;
    private $password = null;
    private $cluster = null;
    private $bucket = null;
    private $scope = null;

    public function __construct()
    {
        $this->host = "couchbase://".config('database.connections.couchbase.driver');
        $this->username = config('database.connections.couchbase.username');
        $this->password = config('database.connections.couchbase.password');
        $this->bucket = config('database.connections.couchbase.database');
        $this->scope = 'game';

        $options = new ClusterOptions();
        $options->credentials($this->username, $this->password);
        $this->cluster = new Cluster($this->host, $options);
    }

    public function getAllCollections() {
        
    }

    public function insert(String $collectionName, String $document, array $data = []) {
        $bucket = $this->cluster->bucket($this->bucket);

        // Get all collections in the bucket
        $scope = $bucket->scope($this->scope);
        $collection = $scope->collection($collectionName);
        
        // Insert a document
        $collection->upsert($document, $data);

        // Get a document
        $result = $collection->get($document);
        var_dump($result->content());
    }

    public function remove(String $collectionName, String $document) {
        $bucket = $this->cluster->bucket($this->bucket);

        // Get all collections in the bucket
        $scope = $bucket->scope($this->scope);
        $collection = $scope->collection($collectionName);
        
        // Remove a document
        $collection->remove($document);
    }
}
