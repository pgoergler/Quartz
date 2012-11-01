<?php

namespace Quartz\Connection;

class Pgsql84Connection extends Connection
{

    protected $rConnect;
    protected $rLastQuery;
    protected $sLastQuery;
    protected $isPersistant = false;

    public function configure()
    {
        $this->registerConverter('Array', new \Quartz\Converter\PgSQL\ArrayConverter($this), array());
        $this->registerConverter('Json', new \Quartz\Converter\Common\JsonConverter(), array('json'));
        $this->registerConverter('Boolean', new \Quartz\Converter\Common\BooleanConverter(), array('bool', 'boolean'));
        $this->registerConverter('Number', new \Quartz\Converter\Common\NumberConverter(), array('int2', 'int4', 'int8', 'numeric', 'float4', 'float8', 'integer', 'sequence'));
        $this->registerConverter('String', new \Quartz\Converter\Common\StringConverter(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'string', 'enum'));
        $this->registerConverter('Timestamp', new \Quartz\Converter\PgSQL\TimestampConverter(), array('timestamp', 'date', 'time', 'datetime', 'unixtime'));
        $this->registerConverter('HStore', new \Quartz\Converter\PgSQL84\HStoreConverter(), array('hstore'));
        //$this->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        //$this->registerConverter('Binary', new Converter\PgBytea(), array('bytea'));
    }

    public function connect()
    {

        $host = $this->hostname;
        $port = null;
        if (preg_match('#^(.*?):([0-9]+)$#', $host, $m))
        {
            $host = $m[1];
            $port = $m[2];
        }

        $connect = 'host=' . $host . (is_null($port) ? '' : ' port=' . $port);

        $connect .= (is_null($this->user) ? '' : ' user=' . $this->user);
        $connect .= ((is_null($this->password) || $this->password == '') ? '' : ' password=' . $this->password);

        $connect .= ' dbname=' . $this->dbname;


        if (isset($this->extra['persistant']) && $this->extra['persistant'])
        {
            $this->rConnect = @pg_pconnect($connect);
            $this->isPersistant = true;
        } else
        {
            $this->rConnect = @pg_pconnect($connect, PGSQL_CONNECT_FORCE_NEW);
        }

        if (!$this->rConnect)
        {
            throw new \RuntimeException("CONNECTION ERROR To(" . $connect . ") : " . $this->error());
        }

        $this->closed = false;
    }

    public function close()
    {
        $this->closed = true;
        if (!$this->isPersistant)
            @pg_close($this->rConnect);
    }

    public function isClosed()
    {
        return $this->closed;
    }

    public function &getConnection()
    {
        return $this->rConnect;
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
        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;
        $res = $this->query('SELECT nextval(\'' . $tableName . '_' . $key . '_seq\'::regclass) AS counter;');
        $row = $this->farray($res);
        $this->free($res);
        return $row['counter'];
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

        //Logger::getRootLogger()->trace($tableName);
        //Logger::getRootLogger()->trace($criteria);

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
                . (is_null($limit) ? '' : ' LIMIT ' . $limit . (is_null($offset) ? '' : ' OFFSET ' . ($offset * $limit)));
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
        $newObjectClass = $table->getObjectClassName();

        if ($object instanceof \Quartz\Object\Entity)
        {
            //$object = $object->toArray();
        }

        //$primaries = $table->getPrimaryKeys();
        /* foreach ($primaries as $primary)
          {
          if ($table->getPropertyType($primary) == 'sequence')
          {
          $res = $this->query('SELECT nextval(\'' . $table->getName() . '_' . $primary . '_seq\'::regclass) AS counter;');
          $row = $this->farray($res);
          $this->free($res);
          $object[$primary] = $row['counter'];
          }
          } */

        //Logger::getRootLogger()->trace($row);

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

        //Logger::getRootLogger()->trace($where);
        $query = 'DELETE FROM ' . $tableName . ((count($where) > 0) ? ' WHERE ' . implode(' AND ', $where) : '' ) . ";";
        return $this->query($query);
    }

    public function begin()
    {
        return $this->query('BEGIN TRANSACTION;');
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

        //Logger::getRootLogger()->trace($sQuery);

        $this->sLastQuery = $sQuery;

        $this->rLastQuery = @pg_query($this->rConnect, $sQuery);

        if ($this->error())
            throw new \RuntimeException($sQuery . "\n" . $this->error());

        return $this->rLastQuery;
    }

    /* iResultType:
     * PGSQL_NUM: les indices du tableau sont des entiers.
     * PGSQL_ASSOC: les indices du tableau sont les noms des clefs
     */

    public function farray($rQuery = null, $callback = null, $iResultType = PGSQL_ASSOC)
    {
        $iResultType = ($iResultType == null) ? PGSQL_ASSOC : $iResultType;
        if ($rQuery == null)
            $rQuery = $this->rLastQuery;

        $row = @pg_fetch_array($rQuery, null, $iResultType);
        if ($row && !is_null($callback) && is_callable($callback))
        {
            $row = $callback($row);
        }
        return $row;
    }

    public function fall($rQuery = null, $callback = null, $iResultType = PGSQL_ASSOC)
    {
        //$iResultType = ($iResultType == null) ? PGSQL_ASSOC : $iResultType;
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
        
        if(is_resource($rQuery))
            return pg_free_result($rQuery);

        return null;
    }

    /* Renvoie le dernier id de la requete INSERT */

    public function lastid()
    {
        //return @mysql_insert_id();
    }

    public function error()
    {
        return @pg_last_error($this->rConnect);
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
            return $value ? "'t'" : "'f'";
        }

        return $value;
    }

}