<?php

namespace Quartz\Connection;

/**
 * Description of MysqliConnection
 *
 * @author paul
 */
class MysqliConnection extends AbstractTransactionalConnection
{
    protected $mysqli;
    protected $rLastQuery;
    protected $sLastQuery;
    protected $isPersistant = false;
    
    protected function beginImplementation()
    {
        return $this->query('START TRANSACTION;');
    }

    protected function commitImplementation()
    {
        return $this->query('COMMIT;');
    }

    protected function rollbackImplementation()
    {
        return $this->query('ROLLBACK;');
    }

    public function commitSavepoint($savepoint)
    {
        return $this->query("RELEASE SAVEPOINT $savepoint;");
    }

    public function rollbackSavepoint($savepoint)
    {
        return $this->query("ROLLBACK TO SAVEPOINT $savepoint;");
    }

    public function savepoint($savepoint)
    {
        return $this->query("SAVEPOINT $savepoint;");
    }

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
        try
        {
            $this->mysqli = new \mysqli($host, $this->user, $this->password, $this->dbname, $port);
        } catch( \Exception $e)
        {
            throw new \RuntimeException("CONNECTION ERROR To(" . $this->hostname . ") : " . $this->error());
        }

        if (\mysqli_connect_error())
        {
            throw new \RuntimeException("CONNECTION ERROR To(" . $this->hostname . ") : " . $this->error());
        }

        $this->closed = false;
    }

    public function close($force = false)
    {
        $this->closed = true;
        if ($force || !$this->isPersistant)
        {
            $this->mysqli->close();
        }
    }

    public function configure()
    {
        $this->registerConverter('Array', new \Quartz\Converter\MySQL\ArrayConverter($this), array());
        $this->registerConverter('Boolean', new \Quartz\Converter\MySQL\BooleanConverter(), array('bool', 'boolean'));
        $this->registerConverter('Number', new \Quartz\Converter\MySQL\NumberConverter(), array('smallint', 'smallinteger', 'int2', 'int4', 'int8', 'numeric', 'double', 'float4', 'float8', 'integer', 'biginteger', 'sequence', 'bigsequence', 'serial', 'bigserial'));
        $this->registerConverter('String', new \Quartz\Converter\MySQL\StringConverter(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'string', 'enum'));
        $this->registerConverter('Timestamp', new \Quartz\Converter\MySQL\TimestampConverter(), array('timestamp', 'date', 'time', 'datetime', 'unixtime'));
        $this->registerConverter('HStore', new \Quartz\Converter\MySQL\HStoreConverter(), array('hstore'));
    }
    
    public function convertType($type)
    {
        $type = \strtolower($type);
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
                return array($rootType, $parameter, $arraySize);
        }
    }
    
    public function countRows($resource)
    {
        if( $resource && $resource instanceof \mysqli_result)
        {
            return \mysqli_num_rows($resource);
        }
        return 0;
    }

    public function create(\Quartz\Object\Table $table)
    {
        $sql = "CREATE TABLE %table_name% ( %fields%, %constraints% );";

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
                $constraints[] = 'UNIQUE INDEX ' . $this->escapeField($tableSlugname . '_' . $columnName . '_ukey') . ' UNIQUE (' . $this->escapeField($columnName) . ' ASC)';
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
        $query = 'DELETE FROM ' . $tableName . ((count($where) > 0) ? ' WHERE ' . implode(' AND ', $where) : '' ) . ";";
        $this->query($query);
        
        return null;
    }

    public function drop(\Quartz\Object\Table $table, $cascade = false)
    {
        $query = 'DROP TABLE IF EXISTS ' . $table->getName() . ( $cascade ? ' CASCADE' : '') . ';';
        return $this->query($query);
    }

    public function error()
    {
        if( $this->mysqli && \mysqli_connect_error() ){
            return \mysqli_connect_error();
        }
        elseif( $this->mysqli && \mysqli_error($this->mysqli) )
        {
            return \mysqli_error($this->mysqli);
        }
    }

    public function errorCode()
    {
        if( \mysqli_connect_error() ){
            return \mysqli_connect_error();
        }
        return \mysqli_error($this->mysqli);
    }

    public function escapeBinary($value)
    {
        return $value;
    }

    public function escapeField($field)
    {
        return '`' . $field . '`';
    }

    public function escapeNumber($value, $type = 'integer')
    {
        settype($value, $type);
        return $value;
    }

    public function escapeString($value)
    {
        if( $this->isClosed() )
        {
            $this->connect();
        }
        return $this->mysqli->real_escape_string($value);
    }

    public function fetchRow($resource, $index = null)
    {
        $resource->data_seek($index);
        return $resource->fetch_array(MYSQLI_ASSOC);
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
                . (is_null($limit) ? '' : ' LIMIT ' . $limit . ( !$offset ? '' : ', ' . $offset));

        if ($forUpdate)
        {
            $query .= ' FOR UPDATE';
        }
        $query .= ";";

        return new \Quartz\Object\Collection($this, $this->query($query));
    }

    public function free($resource)
    {
        if ($resource == null)
        {
            $resource = $this->rLastQuery;
        }
        if( is_resource($resource) )
        {
            \mysqli_free_result($resource);
            return true;
        }
        return null;
    }

    public function &getRaw()
    {
        return $this->mysqli;
    }
    
    public function lastid()
    {
        return $this->mysqli->insert_id;
    }

    protected function refreshObjectQuery(\Quartz\Object\Table $table, $object)
    {
        $lastId = $this->lastid();        
        $pks = array();
        foreach( $table->getColumns() as $name => $property)
        {
            if( $property['primary'])
            {
                if( $property['type'] == 'sequence' )
                {
                    $pks[] = sprintf("%s = %d", $name, $lastId);
                }
                else
                {
                    $pks[] = sprintf("%s = %s", $name, $object[$name]);
                }
            }
        }
        
        $res = $this->query('SELECT * FROM ' . $table->getName() . ' WHERE ' . implode(' AND ', $pks));
        return new \Quartz\Object\Collection($this, $res);
    }
    
    public function insert($table, $object, $returning = '*')
    {
        $tableName = ($table instanceof \Quartz\Object\Table) ? $table->getName() : $table;
        $query = "INSERT INTO %s ( %s ) VALUES (%s);";
        $fields = implode(', ', array_map(array($this, 'escapeField'), array_keys($object)));
        $this->query(sprintf($query, $tableName, $fields, implode(",", $object), $fields));

        return ($table instanceof \Quartz\Object\Table) ? $this->refreshObjectQuery($table, $object) : null;
    }

    public function isClosed()
    {
        return $this->closed;
    }

    protected function doQuery(\mysqli $connection, $query, $maxRetry = 10)
    {
        while($maxRetry > 0)
        {
            try
            {
                return \mysqli_query($connection, $query);
            } catch (\Exception $ex) {
                if( !strstr($e->getMessage(), 'MySQL server has gone away') )
                {
                    throw $ex;
                }
            }
            $maxRetry--;
        }
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
        $this->rLastQuery = $this->doQuery($this->mysqli, $query);
        if( $this->errorCode() )
        {
            throw new \Quartz\Exception\SqlQueryException($query, $this->error(), $this);
        }
        return $this->rLastQuery;
    }

    public function update($table, $query, $object, $returning = '*', $options = array())
    {
        $self = &$this;
        $callback = function($k, $v) use(&$self)
        {
            return $self->escapeField($k) . ' = ' . $v . " ";
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
        $query .= ";";
        
        $this->query($query);
        
        $res = $this->query('SELECT * FROM ' . $tableName . ' WHERE ' . implode(' AND ', $where));
        return new \Quartz\Object\Collection($this, $res);
    }

}
