<?php

namespace Quartz\Connection;

/**
 * Description of AbstractConnection
 *
 * @author paul
 */
abstract class AbstractTransactionalConnection implements Connection
{

    protected $hostname;
    protected $user;
    protected $password;
    protected $dbname;
    protected $extra = array();
    protected $converters = array();
    protected $allowedTypes = array();
    protected $transactions = array();
    protected $closed = true;

    public function __construct($hostname, $user, $password, $dbname, $extra = array())
    {
        $this->hostname = $hostname;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
        $this->extra = $extra;
        $this->closed = true;

        $this->configure();
    }

    public abstract function configure();

    public function begin()
    {
        if (empty($this->transactions))
        {
            $this->transactions[] = 'main';
            return $this->beginImplementation();
        } else
        {
            $savepoint = uniqid("transaction_");
            $this->transactions[] = $savepoint;
            return $this->savepoint($savepoint);
        }
    }

    protected abstract function beginImplementation();

    public function commit()
    {
        $savepoint = array_pop($this->transactions);
        if ($savepoint === 'main')
        {
            return $this->commitImplementation();
        } else
        {
            return $this->commitSavepoint($savepoint);
        }
    }

    protected abstract function commitImplementation();

    public function escape($value, $type = 'string')
    {
        if (is_null($value))
        {
            return $value;
        }

        $arrayType = null;
        if (preg_match('#^([a-z0-9_\.-]+)\[(.*?)\]$#i', $type, $matchs))
        {
            $type = 'array';
            $arrayType = $matchs[1];
        }

        switch ($type)
        {
            case 'sequence':
                return $this->escapeNumber($value, 'integer');
            case 'integer':
            case 'float':
            case 'double':
                return $this->escapeNumber($value, $type);
            case 'array':
                $self = &$this;
                return array_map(function($item) use(&$self, $arrayType)
                {
                    return $self->escape($item, $arrayType);
                }, $value);
            case 'boolean':
                return $value ? true : false;
            case 'binary':
                return $this->escapeBinary($value);
            default:
                return $this->escapeString($value);
        }
    }

    public function getConverterFor($name)
    {
        return array_key_exists($name, $this->converters) ? $this->converters[$name] : null;
    }

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

        throw new \Exception(sprintf("Could not find a converter for type '%s'.", $type));
    }

    public function getDatabaseName()
    {
        return $this->dbname;
    }

    public function registerConverter($name, \Quartz\Converter\Converter $converter, array $types = array())
    {
        $this->converters[$name] = $converter;
        foreach ($types as $type)
        {
            $this->allowedTypes[$type] = $name;
        }

        return $this;
    }

    public function registerTypeForConverter($type, $converter_name)
    {
        $this->allowedTypes[$type] = $converter_name;

        return $this;
    }

    public function rollback($force = false)
    {
        $savepoint = array_pop($this->transactions);
        if ($savepoint === 'main' || $force)
        {
            $this->transactions = array();
            return $this->rollbackImplementation();
        } else
        {
            return $this->rollbackSavepoint($savepoint);
        }
    }

    protected abstract function rollbackImplementation();

    public function extractType($type)
    {
        if (preg_match('#^([a-z0-9_\.-]+)$#i', $type, $matchs))
        {
            return $matchs[1];
        } else if (preg_match('#^([a-z0-9_\.-]+)\[(.*?)\]$#i', $type, $matchs))
        {
            return 'Array';
        } else if (preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
        {
            return $matchs[1];
        } else
        {
            
        }
        return $type;
    }

    protected function getTypeFromValue($value)
    {
        if (is_null($value))
        {
            return 'null';
        }

        if (is_string($value))
        {
            return 'string';
        }

        if (is_bool($value))
        {
            return 'boolean';
        }

        if (is_int($value))
        {
            return 'integer';
        }

        if (is_float($value))
        {
            return 'float';
        }

        if (is_long($value))
        {
            return 'long';
        }

        if (is_double($value))
        {
            return 'double';
        }

        if (is_array($value))
        {
            return 'array';
        }

        if ($value instanceof \DateTime)
        {
            return 'timestamp';
        }

        return 'string';
    }

    public function convertFromDb($value, $type = null)
    {
        $rootType = $this->extractType($type);
        $converter = $this->getConverterForType($rootType);
        return $converter->fromDb($value, $type, null);
    }

    public function convertToDb($value, $type = null)
    {
        $rootType = $this->extractType($type);
        $converter = $this->getConverterForType($rootType);
        return $converter->toDb($value, $type, null);
    }

}
