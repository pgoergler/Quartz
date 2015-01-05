<?php

namespace Quartz\Exception;

class SqlException extends \Exception
{
    protected $connection;
    protected $query;

    public function __construct(\Quartz\Connection\Connection &$connection = null, $query = null, $previous = null)
    {
        parent::__construct($connection->error() . " in query:\n" . $query, 0, $previous);
        $this->query = $query;
        $this->connection = $connection;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function getQuery()
    {
        return $this->query;
    }

    public function getErrorCode() {
        return $this->connection ? $this->connection->errorCode() : false;
    }
    
    public function getErrorMessage() {
        return $this->connection ? $this->connection->error() : false;
    }
    
}