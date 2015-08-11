<?php

namespace Quartz\Object;

/**
 * Description of Table
 *
 * @author paul
 */
class Table
{

    protected $orm = null;
    protected $connection = null;
    protected $databaseName = null;
    protected $primaryKeys = array();
    protected $name = null;
    protected $objectClassName = null;
    protected $properties = null;
    protected $hasOne = array();
    protected $hasMany = array();
    protected $hasAndBelongsToMany = array();
    protected $mappedProperty = array();

    public function __construct(\Quartz\Connection\Connection $conn = null, \Quartz\Quartz $orm = null)
    {
        if (!is_null($conn))
        {
            $this->setConnection($conn);
            $this->setDatabaseName($this->getConnection()->getDatabaseName());
        } else
        {
            if (is_null($this->getConnection()))
            {
                $this->setConnection(\Quartz\Quartz::getInstance()->getConnection('default'));
            }

            if (is_null($this->getDatabaseName()))
            {
                $this->setDatabaseName(\Quartz\Quartz::getInstance()->getDatabaseName('default'));
            }
        }

        if (!is_null($orm))
        {
            $this->orm = $orm;
        } else
        {
            $this->orm = \Quartz\Quartz::getInstance();
        }
    }

    /**
     *
     * @param \Quartz\Quartz $orm
     * @return \Quartz\Object\Table
     */
    public function setOrm(\Quartz\Quartz $orm)
    {
        $this->orm = $orm;
        return $this;
    }

    /**
     *
     * @return \Quartz\Quartz
     */
    public function getOrm()
    {
        return $this->orm;
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

    public function getObjectClassName($values = array())
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
        list($typeClean, $typeParameter, $array) = $this->getConnection()->convertType($type);
        $conf = array(
            'type' => $typeClean,
            'type_parameter' => $typeParameter,
            'array_infos' => $array,
            'value' => $defaultValue,
            'notnull' => ($notnull) ? true : false,
            'primary' => false,
            'unique' => false,
        );

        switch ($typeClean)
        {
            case 'enum':
                $conf['values'] = (isset($options['values']) && is_array($options['values']) ) ? $options['values'] : array();
                break;
        }

        if (isset($options['primary']) && $options['primary'] === true)
        {
            $conf['primary'] = true;
            $this->addPrimaryKey($property);
        }

        if (isset($options['unique']) && $options['unique'] === true)
        {
            $conf['unique'] = true;
        }

        $this->properties[$property] = $conf;
        $camelCase = preg_replace_callback('#(^|_)(.)#', function($matches){
            return strtoupper($matches[2]);
        }, $property);
        $this->mappedProperty[$camelCase] = $property;
    }

    public function getRealPropertyName($property)
    {
        if( isset($this->mappedProperty[$property]) )
        {
            return $this->mappedProperty[$property];
        }
        
        $check = array(
            strtolower($property),
            strtolower(preg_replace('/(?<!^)([A-Z][a-z]|[0-9]+|(?<=[a-z0-9])[^a-z0-9]|(?<=[A-Z])[0-9_])/', '_$1', $property)),
            $property,
            "_$property",
            "_" . strtolower($property),
            preg_replace('/(?<!^)([A-Z][a-z]|[0-9]+|(?<=[a-z0-9])[^a-z0-9]|(?<=[A-Z])[0-9_])/', '_$1', $property),
        );

        foreach ($check as $p)
        {
            if ($this->hasProperty($p))
            {
                return $p;
            }
        }
        throw new \Quartz\Exception\NotExistsException('In ' . $this->getObjectClassName() . ' [' . $property . '] property');
    }

    public function getColumns()
    {
        return $this->properties;
    }

    public function getColumn($field)
    {
        if ($this->hasProperty($field))
        {
            return $this->properties[$field];
        }
        return null;
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
    
    public function getPropertyTypeParameter($property)
    {
        if ($this->hasProperty($property))
        {
            return $this->properties[$property]['type_parameter'];
        }
        return null;
    }
    
    public function isPropertyArray($property)
    {
        if ($this->hasProperty($property))
        {
            return $this->properties[$property]['array_infos'] !== false;
        }
        return false;
    }
    
    public function getPropertyTypeArraySize($property)
    {
        if ($this->hasProperty($property) && $this->isPropertyArray($property))
        {
            return $this->properties[$property]['array_infos'];
        }
        return 0;
    }

    public function getEnumValues($property)
    {
        if ($this->hasProperty($property))
        {
            return $this->properties[$property]['values'];
        }
        throw new \Quartz\Exception\NotExistsException('In ' . $this->getName() . ' ' . $property . ' property');
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
        throw new \Quartz\Exception\NotExistsException('In ' . $this->name . ' ' . $property . ' property');
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
     * @throws \Quartz\Exception\NotExistsException
     */
    public function getOneRelation($className)
    {
        if ($this->hasOneRelation($className))
        {
            return $this->hasOne[$className];
        }
        throw new \Quartz\Exception\NotExistsException('In ' . $this->name . ' ' . $className);
    }

    /**
     *
     * @param string $className
     * @return array
     * @throws \Quartz\Exception\NotExistsException
     */
    public function getManyRelation($className)
    {
        if ($this->hasManyRelation($className))
        {
            return $this->hasMany[$className];
        }
        throw new \Quartz\Exception\NotExistsException('In ' . $this->name . ' ' . $className);
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

            return $this->$function($property, $args[0], isset($args[1]) ? $args[1] : null);
        }
        throw new \RuntimeException($methodName . ' not implemented in ' . get_class($this));
    }

    public function convertFromDb(array $object = null, Entity $entity = null)
    {
        if (is_null($object))
        {
            return null;
        }

        $values = array();

        foreach ($this->getProperties() as $property)
        {
            if (!array_key_exists($property, $object))
            {
                $values[$property] = $this->getDefaultValue($property);
            } else
            {
                $type = $this->getPropertyType($property);
                $typeParameter = $this->getPropertyTypeParameter($property);
                if( $this->isPropertyArray($property) )
                {
                    $elementConverter = $this->getConnection()->getConverterForType($type);
                    $convertFn = function($value) use (&$elementConverter, $type, $typeParameter) {
                        return $elementConverter->fromDb($value, $type, $typeParameter);
                    };
                    $converter = $this->getConnection()->getConverterFor('Array');
                    $nvalue = $converter->fromDb($object[$property], $type, array(
                        'size' => $this->getPropertyTypeArraySize($property),
                        'converter' => $convertFn
                    ));
                } else
                {
                    $converter = $this->getConnection()->getConverterForType($type);
                    $nvalue = $converter->fromDb($object[$property], $type, $typeParameter);
                }
                $values[$property] = $nvalue;
            }
        }

        $className = $this->getObjectClassName($values);
        if( is_null($entity) )
        {
            $entity = new $className();
        }
        $this->hydrate($entity, $values);
        $entity->setNew(false);
        $entity->setModified(false);
        return $entity;
    }

    public function convertPropertyValueToDb($property, $value)
    {
        $type = $this->getPropertyType($property);
        $typeParameter = $this->getPropertyTypeParameter($property);
        if( $this->isPropertyArray($property) )
        {
            $elementConverter = $this->getConnection()->getConverterForType($type);
            $convertFn = function($value) use (&$elementConverter, $type, $typeParameter) {
                return $elementConverter->toDb($value, $type, $typeParameter);
            };
            $converter = $this->getConnection()->getConverterFor('Array');
            $nvalue = $converter->ToDb($value, $type, array(
                'size' => $this->getPropertyTypeArraySize($property),
                'converter' => $convertFn
            ));
        } else
        {
            $converter = $this->getConnection()->getConverterForType($type);
            $nvalue = $converter->toDb($value, $type, $typeParameter);
        }
        return $nvalue;
    }

    public function convertToDb($object)
    {
        $row = array();
        try
        {
            foreach ($object as $property => $value)
            {
                if (!$this->hasProperty($property))
                {
                    continue;
                }
                
                if( in_array($this->getPropertyType($property), array('sequence', 'bigsequence', 'serial', 'bigserial')) && is_null($value) )
                {
                    continue;
                }
                $row[$property] = $this->convertPropertyValueToDb($property, $value);
            }
        } catch (\Exception $e)
        {
            \Logging\LoggersManager::getInstance()->get()->debug($e);
            if (isset($property))
            {
                \Logging\LoggersManager::getInstance()->get()->debug($object instanceof Entity ? $object->toArray() : $object);
                throw new \Quartz\Exception\FieldFormatException($property, $value, $e->getMessage());
            }
            throw $e;
        }
        return $row;
    }

    public function hydrate(Entity &$entity, $row)
    {
        $values = array();
        foreach ($this->getProperties() as $property)
        {
            if (!array_key_exists($property, $row))
            {
                $values[$property] = $this->getDefaultValue($property);
            } else
            {
                $values[$property] = $row[$property];
            }
        }
        $entity->hydrate($values);
        return $this;
    }

    public function save(Entity &$entity)
    {
        $conn = $this->getConnection();
        $newObj = null;
        $returning = implode(', ', array_map(array($this->getConnection(), 'escapeField'), $this->getProperties()));
        if ($entity->isNew())
        {
            $collection = $conn->insert($this, $this->convertToDb($entity), $returning);
            if( $collection->count() )
            {
                $newObj = $this->convertFromDb($collection->current(), $entity);
            }
        } else
        {
            if ($entity->isModified())
            {
                $pKey = $this->getPrimaryKeys();
                $previousValues = $entity->getPreviousValues();
                $query = array();
                foreach ($pKey as $pk)
                {
                    $query[$pk] = $this->convertPropertyValueToDb($pk, array_key_exists($pk, $previousValues) ? $previousValues[$pk] : $entity->get($pk));
                }

                $values = $this->convertToDb($entity->getValuesUpdated());
                
                $collection = $conn->update($this->getName(), $query, $values, $returning);
                if( $collection->count() )
                {
                    $newObj = $this->convertFromDb($collection->current(), $entity);
                }
            }
        }
    }

    /**
     *
     * @param array $criteria
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @param boolean $forUpdate
     * @return Collection
     */
    public function find(array $criteria = array(), array $order = null, $limit = null, $offset = 0, $forUpdate = false)
    {
        $res = $this->getConnection()->find($this, $criteria, $order, $limit, $offset, $forUpdate);
        $self = $this;
        $res->registerFilter(function($item) use($self)
        {
            return $self->convertFromDb($item);
        });
        return $res;
    }

    /**
     *
     * @param array $criteria
     * @param boolean $forUpdate
     */
    public function findOne(array $criteria = array(), array $order = null, $forUpdate = false)
    {
        $res = $this->find($criteria, $order, 1, 0, $forUpdate);
        if ($res->count() > 0)
        {
            return $res->current();
        }
        return null;
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return array of Entity
     */
    public function findBy($property, $value, $forUpdate = false)
    {
        $query = array(
            $property => $this->escapeProperty($property, $value),
        );

        $primary = $this->getPrimaryKeys();
        $order = array();
        foreach ($primary as $value)
        {
            $order[$value] = 1;
        }

        return $this->find($query, $order, null, 0, $forUpdate);
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return Entity
     */
    public function findOneBy($property, $value, $forUpdate = false)
    {
        $res = $this->findBy($property, $value, $forUpdate);
        if ($res->count() > 0)
        {
            return $res->current();
        }
        return null;
    }

    public function delete(Entity $entity)
    {
        $pKey = $this->getPrimaryKeys();
        $query = array();
        
        $query = array();
        foreach ($pKey as $pk)
        {
            $query[$pk] = $this->convertPropertyValueToDb($pk, $entity->get($pk));
        }
        
        $conn = $this->getConnection();
        $conn->delete($this->getName(), $query);
        return $entity;
    }

    
    /*public function delete(array $criteria = array(), array $options = array())
    {
        return $this->getConnection()->delete($this, $criteria, $options);
    }*/

    public function escapeProperty($property, $value)
    {
        $type = $this->getPropertyType($property);
        return $this->getConnection()->escape($value, $type);
    }

    public function escape($value, $type = 'string')
    {
        return $this->getConnection()->escape($value, $type);
    }

    public function create()
    {
        return $this->getConnection()->create($this);
    }

    public function drop($cascade = false)
    {
        return $this->getConnection()->drop($this, $cascade);
    }

}
