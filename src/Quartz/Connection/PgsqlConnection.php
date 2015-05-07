<?php

namespace Quartz\Connection;

/**
 * Description of PgsqlConnection
 *
 * @author paul
 */
class PgsqlConnection extends AbstractTransactionalConnection
{

    protected $rConnect;
    protected $rLastQuery;
    protected $sLastQuery;
    protected $isPersistant = false;

    protected function beginImplementation()
    {
        return $this->query('BEGIN TRANSACTION;');
    }

    protected function commitImplementation()
    {
        return $this->query('COMMIT;');
    }

    protected function rollbackImplementation()
    {
        return $this->query('ROLLBACK;');
    }

    public function close()
    {
        $this->closed = true;
        if (!$this->isPersistant && $this->rConnect)
        {
            @pg_close($this->rConnect);
            $this->rConnect = null;
        }
    }

    public function commitSavepoint($savepoint)
    {
        return $this->query("RELEASE SAVEPOINT $savepoint;");
    }

    public function configure()
    {
        $this->registerConverter('Array', new \Quartz\Converter\PgSQL\ArrayConverter($this), array('array'));
        $this->registerConverter('Json', new \Quartz\Converter\PgSQL\JsonConverter(), array('json'));
        $this->registerConverter('Boolean', new \Quartz\Converter\PgSQL\BooleanConverter(), array('bool', 'boolean'));
        $this->registerConverter('Number', new \Quartz\Converter\PgSQL\NumberConverter(), array('int2', 'int4', 'int8', 'numeric', 'float4', 'float8', 'integer', 'sequence'));
        $this->registerConverter('String', new \Quartz\Converter\PgSQL\StringConverter(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'string', 'enum'));
        $this->registerConverter('Timestamp', new \Quartz\Converter\PgSQL\TimestampConverter(), array('timestamp', 'date', 'time', 'datetime', 'unixtime'));
        $this->registerConverter('TimestampTZ', new \Quartz\Converter\PgSQL\TimestampWithTimezoneConverter(), array('timestamptz', 'timestamp with time zone', 'timestamp with timezone'));
        $this->registerConverter('HStore', new \Quartz\Converter\PgSQL\HStoreConverter(), array('hstore'));
        $this->registerConverter('Interval', new \Quartz\Converter\PgSQL\IntervalConverter(), array('interval'));
        $this->registerConverter('Binary', new \Quartz\Converter\PgSQL\ByteaConverter(), array('binary', 'bytea'));
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
            $this->rConnect = @pg_connect($connect, PGSQL_CONNECT_FORCE_NEW);
        }

        if (!$this->rConnect)
        {
            throw new \Exception("CONNECTION ERROR To(" . $connect . ") : " . $this->error());
        }

        $this->closed = false;
    }

    public function convertType($type)
    {
        $rootType = $type;
        $parameter = '';
        $arraySize = false;
        if (preg_match('#^(?P<type>.*?)(\((?P<parameter>.*?)\))?(?P<array>\[(?P<array_size>.*?)\])?$#', $rootType, $m))
        {
            $rootType = $m['type'];
            $parameter = array_key_exists('parameter', $m) ? $m['parameter'] : '';
            $array = array_key_exists('array', $m);
            $arraySize = $array ? ($m['array_size'] ?: null) : false;
        }
        switch ($rootType)
        {
            case 'string':
                return array('varchar', $parameter, $arraySize);
            default:
                return array(\strtolower($rootType), $parameter, $arraySize);
        }
    }
    
    public function countRows($resource)
    {
        if ($resource && is_resource($resource))
        {
            return pg_num_rows($resource);
        }
        return 0;
    }

    public function create(\Quartz\Object\Table $table)
    {
        $sql = "CREATE TABLE %table_name% ( %fields%, %constraints% ) WITH ( OIDS = FALSE );";

        $fields = array();
        $constraints = array();
        $primaries = array();

        $tableSlugname = preg_replace('#^(.*?\.)(.*?)$#', '$2', $table->getName());

        foreach ($table->getColumns() as $columnName => $configuration)
        {
            $type = strtolower($table->getPropertyType($columnName));
            $typeParameter = $table->getPropertyTypeParameter($columnName);
            $isArray = $table->isPropertyArray($columnName) ? $table->getPropertyTypeArraySize($columnName) : false;
            $converter = $this->getConverterForType($type);

            $sqlType = $converter->translate($type, $typeParameter) . ( $isArray !== false ? "[$isArray]" : '');
            $notNull = $configuration['notnull'] ? 'NOT NULL' : '';
            $default = is_null($configuration['value']) ? '' : $configuration['value'];

            $fields[] = sprintf('%s %s %s', $this->escapeField($columnName), $sqlType, $notNull, $default);

            if ($configuration['primary'])
            {
                $primaries[] = $this->escapeField($columnName);
            }

            if ($configuration['unique'])
            {
                $constraints[] = 'CONSTRAINT ' . $this->escapeField($tableSlugname . '_' . $columnName . '_ukey') . ' UNIQUE (' . $this->escapeField($columnName) . ')';
            }
        }

        $constraints[] = 'PRIMARY KEY (' . implode(', ', $primaries) . ')';

        $replace = array(
            '%table_name%' => $table->getName(),
            '%fields%' => implode(', ', $fields),
            '%constraints%' => implode(', ', $constraints),
        );

        return $this->query(strtr($sql, $replace));
    }

    public function drop(\Quartz\Object\Table $table, $cascade = false)
    {
        $query = 'DROP TABLE IF EXISTS ' . $table->getName() . ( $cascade ? ' CASCADE' : '') . ';';
        return $this->query($query);
    }
    
    public function error()
    {
        return @pg_last_error($this->rConnect);
    }

    public function escapeBinary($value)
    {
        return pg_escape_bytea($this->rConnect, $value);
    }

    public function escapeNumber($value, $type = 'integer')
    {
        settype($value, $type);
        return $value;
    }

    public function escapeString($value)
    {
        return pg_escape_string($this->rConnect, $value);
    }

    public function fetchRow($resource, $index = null)
    {
        return pg_fetch_array($resource, $index, PGSQL_ASSOC);
    }

    public function &getRaw()
    {
        return $this->rConnect;
    }

    public function isClosed()
    {
        return $this->closed;
    }

    public function query($query, $parameters = array())
    {
        if (is_null($query))
        {
            return null;
        }

        if ($this->isClosed())
        {
            $this->connect();
        }

        $this->sLastQuery = $query;

        $this->rLastQuery = @pg_query($this->rConnect, $query);

        if ($this->error())
        {
            throw new \Quartz\Exception\SqlQueryException($query, $this->error(), $this);
        }
        return $this->rLastQuery;
    }

    public function rollbackSavepoint($savepoint)
    {
        return $this->query("ROLLBACK TO SAVEPOINT $savepoint;");
    }

    public function savepoint($savepoint)
    {
        return $this->query("SAVEPOINT $savepoint;");
    }

    public function escapeField($field)
    {
        return '"' . $field . '"';
    }

    public function free($resource)
    {
        if (is_resource($resource))
        {
            return pg_free_result($resource);
        }

        return null;
    }

    public function delete($tableName, $query, $returning = '*', $options = array())
    {
        $where = array();
        foreach ($query as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = $this->escapeField($k) . ' = ' . $v;
            }
        }

        $tableName = ($tableName instanceof \Quartz\Object\Table) ? $tableName->getName() : $tableName;
        $query = 'DELETE FROM ' . $tableName . ((count($where) > 0) ? ' WHERE ' . implode(' AND ', $where) : '' );
        if( !is_null($returning) )
        {
            $fields = is_array($returning) ? implode(', ', $returning) : "$returning";
            $query .= " RETURNING " . $fields;
        }
        $query .= ";";
        return new \Quartz\Object\Collection($this, $this->query($query));
    }

    public function find($table, array $criteria = array(), $order = null, $limit = null, $offset = 0, $forUpdate = false)
    {
        $orderby = null;
        if (!is_null($order))
        {
            if (is_array($order))
            {
                $sorted = array();
                foreach ($order as $k => $sort)
                {
                    $sorted[] = $k . ( ($sort === 1) ? ' ASC' : ' DESC');
                }
                $orderby = implode(', ', $sorted);
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
                $type = $table->getPropertyType($k);
                $where[] = $this->escapeField($k) . ' = ' . $this->convertToDb($v, $type);
            }
        }

        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $query = 'SELECT * FROM ' . $tableName
                . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where) )
                . (is_null($orderby) || empty($orderby) ? '' : ' ORDER BY ' . $orderby )
                . (is_null($limit) ? '' : ' LIMIT ' . $limit . (is_null($offset) ? '' : ' OFFSET ' . ($offset * $limit)));

        if ($forUpdate)
        {
            $query .= ' FOR UPDATE';
        }

        return new \Quartz\Object\Collection($this, $this->query($query));
    }

    public function insert($table, $object, $returning = '*')
    {
        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;
        $query = "INSERT INTO %s ( %s ) VALUES (%s)";
        if( !is_null($returning) )
        {
            // $fields = is_array($returning) ? implode(', ', array_map(array($this, 'escapeField'), $returning)) : "$returning";
            $fields = is_array($returning) ? implode(', ', $returning) : "$returning";
            $query .= " RETURNING " . $fields;
        }
        $query .= ";";
        
        $fields = implode(', ', array_map(array($this, 'escapeField'), array_keys($object)));
        $res = $this->query(sprintf($query, $tableName, $fields, implode(",", $object), $fields));
        return new \Quartz\Object\Collection($this, $res);
    }

    public function update($table, $query, $object, $returning = '*', $options = array())
    {
        $self = $this;

        $callback = function($k, $v) use($self)
        {
            return $self->escapeField($k) . ' = ' . $v . " "; // no castToSQL because already done by table->convertToDb()
        };

        $where = array();
        foreach ($query as $k => $v)
        {
            if (is_int($k))
            {
                $where[] = $v;
            } else
            {
                $where[] = $this->escapeField($k) . ' = ' . $v;
            }
        }

        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;

        $query = "UPDATE " . $tableName . " SET ";
        $query .= implode(', ', array_map($callback, array_keys($object), $object));
        $query .= " WHERE " . implode(' AND ', $where);
        if( !is_null($returning) )
        {
            $fields = is_array($returning) ? implode(', ', $returning) : "$returning";
            $query .= " RETURNING " . $fields;
        }
        $query .= ";";

        return new \Quartz\Object\Collection($this, $this->query($query));
    }

    protected function castToSQL($value)
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
