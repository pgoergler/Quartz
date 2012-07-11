<?php

namespace Quartz\Object;

/**
 * Description of Table
 *
 * @author paul
 */
class Table
{

    protected $connection = null;
    protected $databaseName = null;
    protected $primaryKeys = array();
    protected $name = null;
    protected $objectClassName = null;
    protected $properties = null;
    protected $hasOne = array();
    protected $hasMany = array();
    protected $hasAndBelongsToMany = array();

    public function __construct(\Quartz\Connection\Connection $conn = null)
    {
        if (!is_null($conn))
        {
            $this->setConnection($conn);
            $this->setDatabaseName($this->getConnection()->getDatabaseName());
        } else
        {
            $this->setConnection(\Quartz\Quartz::getInstance()->getConnection('default'));
            $this->setDatabaseName(\Quartz\Quartz::getInstance()->getDatabaseName('default'));
        }
    }

    /**
     *
     * @param \Quartz\Connection\Connection $db
     * @return \Quartz\Object\Table 
     */
    public function setConnection(\Quartz\Connection\Connection &$db)
    {
        $this->connection = $db;
        return $this;
    }

    /**
     *
     * @return \Quartz\Connection\Connection
     */
    public function &getConnection()
    {
        return $this->connection;
    }

    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    public function setName($tableName)
    {
        $this->name = $tableName;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setObjectClassName($className)
    {
        $this->objectClassName = $className;
    }

    public function getObjectClassName()
    {
        return $this->objectClassName;
    }

    public function addPrimaryKey($property)
    {
        if (!in_array($property, $this->primaryKeys))
        {
            $this->primaryKeys[] = $property;
        }
    }

    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    public function addColumn($property, $type, $defaultValue, $notnull = true, $options = array())
    {
        $conf = array(
            'type' => $this->getConnection()->convertClassType(strtolower($type)),
            'value' => $defaultValue,
            'notnull' => ($notnull) ? true : false,
        );

        switch ($type)
        {
            case 'enum':
                $conf['values'] = (isset($options['values']) && is_array($options['values']) ) ? $options['values'] : array();
                break;
        }

        if (isset($options['primary']) && $options['primary'] === true)
        {
            $this->addPrimaryKey($property);
        }

        $this->properties[$property] = $conf;
    }

    public function getRealPropertyName($property)
    {
        $check = array(
            strtolower($property),
            $property,
            "_$property",
            "_" . strtolower($property),
            strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $property)),
            preg_replace('/(.)([A-Z])/', '$1_$2', $property),
        );

        foreach ($check as $p)
        {
            if ($this->hasProperty($p))
            {
                return $p;
            }
        }
        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->getObjectClassName() . ' [' . $property . '] property');
    }

    public function getProperties()
    {
        return array_keys($this->properties);
    }

    public function hasProperty($property)
    {
        return array_key_exists($property, $this->properties);
    }

    public function getPropertyType($property)
    {
        if ($this->hasProperty($property))
        {
            return $this->properties[$property]['type'];
        }
        return null;
    }

    public function getEnumValues($property)
    {
        if ($this->hasProperty($property))
        {
            return $this->properties[$property]['values'];
        }
        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->getName() . ' ' . $property . ' property');
    }

    public function getDefaultValue($property)
    {
        if ($this->hasProperty($property))
        {
            $value = $this->properties[$property]['value'];
            if (is_callable($value))
            {
                $value = $value();
            }

            return $value;
        }
        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->name . ' ' . $property . ' property');
    }

    /**
     *
     * @return array 
     */
    public function getDefaultValues()
    {
        $values = array();
        foreach ($this->properties as $key => $conf)
        {
            $values[$key] = $this->getDefaultValues();
        }
        return $values;
    }

    /**
     *
     * @return \Quartz\Object\Table 
     */
    public function beginTransaction()
    {
        $this->getConnection()->begin();
        return $this;
    }

    /**
     *
     * @return \Quartz\Object\Table 
     */
    public function commit()
    {
        $this->getConnection()->commit();
        return $this;
    }

    /**
     *
     * @return \Quartz\Object\Table 
     */
    public function rollBack()
    {
        $this->getConnection()->rollback();
        return $this;
    }

    /**
     *
     * @param string $className
     * @param string $local key
     * @param string $foreign key
     * @param string $alias
     * @return \Quartz\Object\Table 
     */
    public function hasOne($className, $local, $foreign, $alias = null, $orderBy = null, $limit = null, $offset = null)
    {
        $alias = is_null($alias) ? $className : $alias;
        $alias = strtolower($alias);
        if (!$this->hasOneRelation($alias))
        {
            $this->hasOne[$alias] = array(
                'class' => $className,
                'local' => $local,
                'foreign' => $foreign,
                'orderBy' => $orderBy,
                'limit' => $limit,
                'offset' => $offset,
            );
        }
        return $this;
    }

    /**
     *
     * @param string $className
     * @param string $local key
     * @param string $foreign key
     * @param string $alias
     * @return \Quartz\Object\Table 
     */
    public function hasMany($className, $local, $foreign, $alias = null, $orderBy = null, $limit = null, $offset = null)
    {
        $alias = is_null($alias) ? $className : $alias;
        $alias = strtolower($alias);
        if (!$this->hasManyRelation($alias))
        {
            $this->hasMany[$alias] = array(
                'class' => $className,
                'local' => $local,
                'foreign' => $foreign,
                'orderBy' => $orderBy,
                'limit' => $limit,
                'offset' => $offset,
            );
        }
        return $this;
    }

    /**
     *
     * @param string $className
     * @return boolean 
     */
    public function hasOneRelation($className)
    {
        return array_key_exists($className, $this->hasOne);
    }

    /**
     *
     * @param string $className
     * @return boolean 
     */
    public function hasManyRelation($className)
    {
        return array_key_exists($className, $this->hasMany);
    }

    /**
     *
     * @param string $className
     * @return array 
     * @throws \Quartz\Exceptions\NotExistsException 
     */
    public function getOneRelation($className)
    {
        if ($this->hasOneRelation($className))
        {
            return $this->hasOne[$className];
        }
        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->name . ' ' . $className);
    }

    /**
     *
     * @param string $className
     * @return array 
     * @throws \Quartz\Exceptions\NotExistsException 
     */
    public function getManyRelation($className)
    {
        if ($this->hasManyRelation($className))
        {
            return $this->hasMany[$className];
        }
        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->name . ' ' . $className);
    }

    /**
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = array())
    {
        return intval($this->getDatabaseConnection()->count($this, $criteria));
    }

    /**
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     * @throws \RuntimeException 
     */
    public function __call($methodName, $args)
    {
        if (preg_match('~^(findBy|findOneBy)([_A-Z])(.*)$~', $methodName, $matches))
        {
            $function = $matches[1];
            $property = $this->getRealPropertyName($matches[2] . $matches[3]);

            return $this->$function($property, $args[0]);
        }
        throw new \RuntimeException($methodName . ' not implemented in ' . get_class($this));
    }

    public function convertFromDb(array $object = null)
    {
        if (is_null($object))
        {
            return null;
        }

        $className = $this->getObjectClassName();
        $obj = new $className();
        $values = array();

        foreach ($this->getProperties() as $property)
        {
            if (!array_key_exists($property, $object))
            {
                $values[$property] = $this->getDefaultValue($property);
            } else
            {
                $type = $this->getPropertyType($property);
                if (preg_match('#^([a-z0-9_\.-]+)$#i', $type, $matchs))
                {
                    $type = $matchs[1];
                    $converter = $this->getConnection()->getConverterForType($type);
                } else if (preg_match('#^([a-z0-9_\.-]+)\[(.*?)\]$#i', $type, $matchs))
                {
                    $type = $matchs[1];
                    $converter = $this->getConnection()->getConverterFor('Array');
                } else if (preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
                {
                    $type = $matchs[1];
                    $converter = $this->getConnection()->getConverterForType($type);
                } else
                {
                    $converter = $this->getConnection()->getConverterForType($type);
                }
                $nvalue = $converter->fromDb($object[$property], $matchs[1]);
                $values[$property] = $nvalue;
            }
        }
        $obj->hydrate($values);
        $obj->setNew(false);
        return $obj;
    }

    public function convertToDb($object)
    {
        $row = array();
        foreach ($object as $property => $value)
        {
            if (!$this->hasProperty($property))
            {
                continue;
            }

            $type = $this->getPropertyType($property);
            if (preg_match('#^([a-z0-9_\.-]+)$#i', $type, $matchs))
            {
                $type = $matchs[1];
                $converter = $this->getConnection()->getConverterForType($type);
            } else if (preg_match('#^([a-z0-9_\.-]+)\[(.*?)\]$#i', $type, $matchs))
            {
                $type = $matchs[1];
                $converter = $this->getConnection()->getConverterFor('Array');
            } else if (preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
            {
                $type = $matchs[1];
                $converter = $this->getConnection()->getConverterForType($type);
            } else
            {
                $converter = $this->getConnection()->getConverterForType($type);
            }

            $nvalue = $converter->toDb($value, $type);
            $row[$property] = $nvalue;
        }
        return $row;
    }

    /**
     *
     * @param array $criteria
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @return array 
     */
    public function find(array $criteria = array(), array $order = null, $limit = null, $offset = 0, $forUpdate = false)
    {
        $res = $this->getConnection()->find($this, $criteria, $order, $limit, $offset, $forUpdate);
        $self = $this;
        $className = $this->getObjectClassName();

        return array_map(function($item) use($self, $className)
                        {
                            return $self->convertFromDb($item);
                            /* $obj = new $className();
                              $values = array();
                              foreach ($self->getProperties() as $property)
                              {
                              if (!isset($item[$property]))
                              {
                              $values[$property] = $self->getDefaultValue($property);
                              } else
                              {
                              $type = $self->getPropertyType($property);
                              if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $type, $matchs))
                              {
                              if (count($matchs) > 2)
                              {
                              $converter = $self->getConnection()
                              ->getConverterFor('Array');
                              } else
                              {
                              $converter = $self->getConnection()
                              ->getConverterForType($type);
                              }
                              }
                              $nvalue = $converter->fromDb($item[$property], $matchs[1]);
                              $values[$property] = $nvalue;
                              }
                              }
                              print_r($values);
                              $obj->hydrate($values);
                              $obj->setNew(false);
                              return $obj; */
                        }, $res);
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return array of Entity 
     */
    public function findBy($property, $value)
    {
        $query = array(
            $property => $value, //$this->checkPropertyValue($property, $value),
        );

        $primary = $this->getPrimaryKeys();
        $order = array();
        foreach ($primary as $value)
        {
            $order[$value] = 1;
        }

        return $this->find($query, $order);
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return Entity 
     */
    public function findOneBy($property, $value)
    {
        $res = $this->findBy($property, $value);
        if (count($res) > 0)
        {
            return array_shift($res);
        }
        return null;
    }

    public function delete(array $criteria = array(), array $options = array())
    {
        return $this->getConnection()->delete($this, $criteria, $options);
    }

}

?>
