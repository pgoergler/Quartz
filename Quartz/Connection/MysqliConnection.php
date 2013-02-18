<?php

namespace Quartz\Connection;

/**
 * Description of MysqliConnection
 *
 * @author paul
 */
class MysqliConnection extends Connection
{

    protected $mysqli;
    protected $rLastQuery;
    protected $sLastQuery;
    protected $isPersistant = false;

    public function configure()
    {
        $this->registerConverter('Array', new \Quartz\Converter\MySQL\ArrayConverter($this), array());
        $this->registerConverter('Boolean', new \Quartz\Converter\MySQL\BooleanConverter(), array('bool', 'boolean'));
        $this->registerConverter('Number', new \Quartz\Converter\MySQL\NumberConverter(), array('int2', 'int4', 'int8', 'numeric', 'float4', 'float8', 'integer', 'sequence'));
        $this->registerConverter('String', new \Quartz\Converter\MySQL\StringConverter(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'string', 'enum'));
        $this->registerConverter('Timestamp', new \Quartz\Converter\MySQL\TimestampConverter(), array('timestamp', 'date', 'time', 'datetime', 'unixtime'));
        $this->registerConverter('HStore', new \Quartz\Converter\MySQL\HStoreConverter(), array('hstore'));
        //$this->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        //$this->registerConverter('Binary', new Converter\PgBytea(), array('bytea'));
    }

    /* public function __construct($hostname, $user, $password, $dbname, $extra = array())
      {
      $this->hostname = $hostname;
      $this->user = $user;
      $this->password = $password;
      $this->dbname = $dbname;
      $this->extra = $extra;
      } */

    public function connect()
    {
        $host = $this->hostname;
        $port = 3306;
        if (preg_match('#^(.*?):([0-9]+)$#', $host, $m))
        {
            $host = $m[1];
            $port = $m[2];
        }




        if (isset($this->extra['persistant']) && $this->extra['persistant'])
        {
            $host = 'p:' . $host;
            $this->isPersistant = true;
        }
        $this->mysqli = new \mysqli($host, $this->user, $this->password, $this->dbname, $port);

        if (\mysqli_connect_error())
        {
            throw new \RuntimeException("CONNECTION ERROR To(" . $this->hostname . ") : " . $this->error());
        }

        $this->closed = false;
    }

    public function close()
    {
        $this->closed = true;
        if (!$this->isPersistant)
            $this->mysqli->close();
    }

    public function isClosed()
    {
        return $this->closed;
    }

    public function &getConnection()
    {
        return $this->mysqli;
    }

    public function convertClassType($type)
    {
        $rootType = $type;
        $extra = '';
        if (preg_match('#^(.*?)(([\(\[])(.*?)([\)\]]))$#', $rootType, $m))
        {
            $rootType = $m[1];
            $extra = $m[2];
        }
        switch ($rootType)
        {
            case 'string':
                return 'varchar' . $extra;
            default:
                return $type;
        }
    }

    public function getSequence($table, $key, array $options = array())
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function command(array $options = array())
    {
        $query = isset($options['query']) ? $options['query'] : null;
        $buffered = isset($options['buffered']) ? $options['buffered'] : false;
        if (isset($options['fetch']) && $options['fetch'])
        {
            $res = $this->query($query, $buffered);
            if (is_callable($options['fetch']))
            {
                return $this->fall($res, $options['fetch']);
            }
            return $this->fall($res);
        }
        return $this->query($query, $buffered);
    }

    public function count($table, array $criteria = array())
    {
        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $where = array();
        foreach ($criteria as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = "$k = " . $this->castToSQL($v);
            }
        }
        if (count($where) == 0)
        {
            $where[] = "1 = 1";
        }

        $query = 'SELECT COUNT(*) as nb FROM ' . $tableName . ' WHERE ' . implode(' AND ', $where) . ';';

        $res = $this->query($query);
        $row = $this->farray($res);
        $this->free($res);
        return $row['nb'];
    }

    public function find($table, array $criteria = array(), $order = null, $limit = null, $offset = 0, $forUpdate = false)
    {
        $orderby = null;
        if (!is_null($order))
        {
            if (is_array($order))
            {
                $orderby = array();
                foreach ($order as $k => $sort)
                {
                    $orderby[] = $k . ( ($sort === 1) ? ' ASC' : ' DESC');
                }
                $orderby = implode(', ', $orderby);
            } else
            {
                $orderby = $order;
            }
        }

        $where = array();
        foreach ($criteria as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = "$k = " . $this->castToSQL($v);
            }
        }

        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $query = 'SELECT * FROM ' . $tableName
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where) )
                . (is_null($orderby) || empty($orderby) ? '' : ' ORDER BY ' . $orderby )
                . (is_null($limit) ? '' : ' LIMIT ' . $limit . (is_null($offset) ? '' : ', ' . $offset));

        if( $forUpdate )
        {
            $query .= ' FOR UPDATE';
        }

        $res = $this->query($query);
        $rows = $this->fall($res);
        $this->free($res);
        return $rows;
    }

    public function insert(\Quartz\Object\Table $table, $object)
    {
        $query = 'INSERT INTO ' . $table->getName() . ' (' . implode(",", array_keys($object)) . ") VALUES (" . implode(",", $object) . ");";

        $this->query($query);
        return $object;
    }

    public function update($table, $query, $object, $options = array())
    {
        $self = $this;

        $callback = function($k, $v) use($self)
                {
                    return " $k = " . $v . " ";
                };

        $where = array();
        foreach ($query as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = "$k = " . $this->castToSQL($v);
            }
        }

        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $query = "UPDATE " . $tableName . " SET ";
        $query .= implode(", ", array_map($callback, array_keys($object), $object));
        $query .= " WHERE " . implode(' AND ', $where) . ";";

        return $this->query($query);
    }

    public function delete(\Quartz\Object\Table $table, $query, $options = array())
    {
        $where = array();
        foreach ($query as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = "$k = " . $this->castToSQL($v);
            }
        }

        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $query = 'DELETE FROM ' . $tableName . ((count($where) > 0) ? ' WHERE ' . implode(' AND ', $where) : '' ) . ";";
        return $this->query($query);
    }

    public function begin()
    {
        return $this->query('START TRANSACTION;');
    }

    public function commit()
    {
        return $this->query('COMMIT;');
    }

    public function rollback()
    {
        return $this->query('ROLLBACK;');
    }

    public function query($sQuery, $unbuffered = false)
    {
        if (is_null($sQuery))
        {
            return null;
        }

        if ($this->isClosed())
        {
            $this->connect();
        }

        $this->sLastQuery = $sQuery;
        $this->rLastQuery = \mysqli_query($this->mysqli, $sQuery);
        if ($this->error())
            throw new \RuntimeException($sQuery . "\n" . $this->error());

        return $this->rLastQuery;
    }

    /* iResultType:
     * MYSQLI_NUM: les indices du tableau sont des entiers.
     * MYSQLI_ASSOC: les indices du tableau sont les noms des clefs
     */

    public function farray($rQuery = null, $callback = null, $iResultType = MYSQLI_ASSOC)
    {
        $iResultType = ($iResultType == null) ? MYSQLI_ASSOC : $iResultType;
        if ($rQuery == null)
            $rQuery = $this->rLastQuery;

        $row = \mysqli_fetch_array($rQuery, $iResultType);
        if ($row && !is_null($callback) && is_callable($callback))
        {
            $row = $callback($row);
        }
        return $row;
    }

    public function fall($rQuery = null, $callback = null, $iResultType = MYSQLI_ASSOC)
    {
        $iResultType = ($iResultType == null) ? MYSQLI_ASSOC : $iResultType;
        $result = array();
        while ($row = $this->farray($rQuery, $callback, $iResultType))
        {
            $result[] = $row;
        }
        return $result;
    }

    public function free($rQuery = null)
    {
        if ($rQuery == null)
            $rQuery = $this->rLastQuery;

        return \mysqli_free_result($rQuery);
    }

    /* Renvoie le dernier id de la requete INSERT */

    public function lastid()
    {
        return @\mysqli_insert_id();
    }

    public function error()
    {
        if( $this->mysqli && \mysqli_connect_error() ){
            return sprintf("[%s] %s", \mysqli_connect_errno(), \mysqli_connect_error() );
        }
    }

    public function castToSQL($value)
    {
        if (is_null($value))
        {
            return 'null';
        }

        if (is_string($value))
        {
            return "'$value'";
        }

        if (is_bool($value))
        {
            return $value ? "1" : "0";
        }

        return $value;
    }

}
