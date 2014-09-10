<?php

namespace Quartz\Connection;

/**
 * Description of Connection
 *
 * @author paul
 */
abstract class Connection
{

    protected $hostname;
    protected $user;
    protected $password;
    protected $dbname;
    protected $extra = array();
    protected $converters = array();
    protected $allowedTypes = array();
    protected $nbTransaction = 0;
    protected $transactions = array();


    public function __construct($hostname, $user, $password, $dbname, $extra = array())
    {
        $this->hostname = $hostname;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->extra = $extra;

        $this->configure();
    }

    abstract public function configure();

    public function getDatabaseName()
    {
        return $this->dbname;
    }

    public function begin()
    {
        if( empty($this->transactions) )
        {
            $this->transactions[] = 'main';
            return $this->__begin();
        }
        else
        {
            $savepoint = uniqid("transaction_");
            $this->transactions[] = $savepoint;
            return $this->__savepoint($savepoint);
        }
    }

    public function commit()
    {
        $savepoint = array_pop($this->transactions);
        if( $savepoint === 'main' )
        {
            return $this->__commit();
        }
        else
        {
            return $this->__commitSavepoint($savepoint);
        }
    }

    public function rollback($force = false)
    {
        $savepoint = array_pop($this->transactions);
        if( $savepoint === 'main' || $force)
        {
            $this->transactions = array();
            return $this->__rollback();
        }
        else
        {
            return $this->__rollbackSavepoint($savepoint);
        }
    }

    public abstract function free($resource);
    
    protected abstract function __savepoint($savepoint);
    
    protected abstract function __commitSavepoint($savepoint);
    
    protected abstract function __rollbackSavepoint($savepoint);
    
    protected abstract function __begin();

    protected abstract function __commit();

    protected abstract function __rollback();

    public abstract function isClosed();

    public abstract function connect();

    public abstract function close();

    public abstract function &getConnection();

    public abstract function getSequence($table, $property, array $options = array());

    public abstract function command(array $options = array());

    public abstract function insert(\Quartz\Object\Table $table, $object);

    public abstract function update($table, $query, $object, $options = array());

    public abstract function delete(\Quartz\Object\Table $table, $query, $options = array());

    public abstract function convertClassType($type);

    /**
     * Escape the value
     *
     * @param mixed $value
     * @param type $type
     * @return mixed
     */
    public function escape($value, $type = 'string')
    {
        if( is_null($value) )
        {
            return $value;
        }
        
        $arrayType = null;
        if (preg_match('#^([a-z0-9_\.-]+)\[(.*?)\]$#i', $type, $matchs))
        {
            $type = 'array';
            $arrayType = $matchs[1];
        }

        switch( $type )
        {
            case 'sequence':
                settype($value, 'integer');
                return $value;
            case 'integer':
            case 'float':
                settype($value, $type);
                return $value;
            case 'array':
                $self = &$this;
                return array_map(function($item) use(&$self, $arrayType) {
                    return $self->escape($item, $arrayType);
                }, $value);
            case 'boolean':
                return $value ? true : false;
            case 'binary':
                return $this->escapeBinary($value);
                break;
            default:
                return $this->escapeString($value);
        }
    }

    protected abstract function escapeBinary($value);

    protected abstract function escapeString($value);

    /**
     * registerConverter
     *
     * Register a new converter
     * @access public
     * @param  String             $name      The name of the converter.
     * @param  ConverterInterface $converter A converter instance.
     * @param  Array              $pg_types  An array of the mapped postgresql's types.
     * @return Quartz\Connection\Connection
     * */
    public function registerConverter($name, \Quartz\Converter\ConverterInterface $converter, array $types = array())
    {
        $this->converters[$name] = $converter;
        foreach ($types as $type)
        {
            $this->allowedTypes[$type] = $name;
        }

        return $this;
    }

    /**
     * getConverterFor
     *
     * Returns a converter from its designation.
     *
     * @access public
     * @param  string $name       Converter desgination.
     * @return \Quartz\Converter\ConverterInterface Converter instance.
     * */
    public function getConverterFor($name)
    {
        return $this->converters[$name];
    }

    /**
     * getConverterForType
     *
     * Returns the converter instance for a given a postgresql's type
     *
     * @access public
     * @param  String $type Type name.
     * @return String Converter instance.
     * @throw  \RuntimeException if not found.
     * */
    public function getConverterForType($type)
    {
        if (isset($this->allowedTypes[$type]))
        {
            $converter_name = $this->allowedTypes[$type];

            if (isset($this->converters[$converter_name]))
            {
                return $this->converters[$converter_name];
            } else
            {
                throw new \Exception(sprintf("Type '%s' is associated with converter '%s' but converter is not registered.", $type, $converter_name));
            }
        }

        throw new \RuntimeException(sprintf("Could not find a converter for type '%s'.", $type));
    }

    /**
     * registerTypeForConverter
     *
     * Associate an existing converter with a Db type.
     * This is useful for DOMAINs.
     *
     * @acces public
     * @param String $type           Type name
     * @param String $converter_name Converter designation.
     * @return Pomm\Connection\Database
     * */
    public function registerTypeForConverter($type, $converter_name)
    {
        $this->allowedTypes[$type] = $converter_name;

        return $this;
    }

    public function updateEntity(\Quartz\Object\Entity $object)
    {
        $pKey = $object->getTable()->getPrimaryKeys(true);
        $query = array();
        foreach ($pKey as $pk)
        {
            $query[$pk] = $object->get($pk);
        }

        return $this->update($object->getTable()->getName(), $query, $object->getValuesUpdated());
    }

    public function deleteEntity(\Quartz\Object\Entity $object)
    {
        $pKey = $object->getTable()->getPrimaryKeys(true);
        $query = array();
        foreach ($pKey as $pk)
        {
            $query[$pk] = $object->get($pk);
        }

        return $this->delete($object->getTable(), $query);
    }

    public abstract function find($table, array $criteria = array(), $order = null, $limit = null, $offset = 0, $forUpdate = false);

    public abstract function count($table, array $criteria = array());

    /**
     * processDsn
     * Sets the different parameters from the DSN
     *
     * @access protected
     * @return void
     */
    public static function processDsn($dsn)
    {
        if (!preg_match('#(?P<driver>[a-z]+)://(?P<user>[^:@]+)(?::(?P<password>[^@]+))?(?:@(?P<host>[\w\.]+|!/.+[^/]!)(?::(\w+))?)?/(?P<database>\w+)#', $dsn, $matchs))
        {
            throw new \Exception(sprintf('Cound not parse DSN "%s".', $dsn));
        }

        if ($matchs['driver'] == null)
        {
            throw new \Exception(sprintf('No protocol information in dsn "%s".', $dsn));
        }
        $driver = $matchs['driver'];

        if ($matchs['user'] == null)
        {
            throw PommException(sprintf('No user information in dsn "%s".', $dsn));
        }
        $user = $matchs['user'];
        $pass = $matchs['password'];

        if (preg_match('/!(.*)!/', $matchs['host'], $host_matchs))
        {
            $host = $host_matchs[1];
        } else
        {
            $host = $matchs['host'];
        }

        $port = $matchs[5];

        if ($matchs['database'] == null)
        {
            throw new \Exception(sprintf('No database name in dsn "%s".', $dsn));
        }
        $database = $matchs['database'];

        return array(
            'driver' => $driver,
            'host' => $host . ($port ? ':' . $port : ''),
            'user' => $user,
            'password' => $pass,
            'database' => $database,
        );
    }


    public function create(\Quartz\Object\Table $table)
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function drop(\Quartz\Object\Table $table, $cascade = false)
    {
        $query = 'DROP TABLE IF EXISTS ' . $table->getName() . ( $cascade ? ' CASCADE' : '') . ';';
        return $this->query($query);
    }
}

?>
