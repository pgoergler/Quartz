<?php

namespace Quartz\Exception;

/**
 * Description of SqlQueryException
 *
 * @author paul
 */
class SqlQueryException extends SqlException
{

    protected $connection;
    protected $query;

    public function __construct($query, $message, \Quartz\Connection\Connection &$connection = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->connection = $connection;
        $this->query = $query;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getQuery()
    {
        return $this->query;
    }

}
