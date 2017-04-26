<?php

namespace Quartz\Object;

/**
 * Description of Entity
 *
 * @author paul
 */
abstract class Entity implements \ArrayAccess, \IteratorAggregate
{

    //-
    protected $status = \Quartz\Quartz::NONE;
    //-
    protected $className = null;
    protected $table = null;
    //-
    protected $values = array();
    protected $oldValues = array();
    protected $valuesUpdated = array();
    //-
    protected $isNew = true;
    protected $exists = false;
    //-
    protected $objectsLinked = array();
    protected $objectsPreSave = array();
    protected $objectsPostSave = array();

    public function __construct(\Quartz\Connection\Connection $conn = null)
    {
        $this->className = get_called_class();

        $table = $this->getTable($conn);

        foreach ($table->getProperties() as $property)
        {
            $this->values[$property] = $table->getDefaultValue($property);
        }
    }

    public static function getTableClassName()
    {
        if (isset(static::$tableClassName) && !is_null(static::$tableClassName))
        {
            return static::$tableClassName;
        }
        return preg_replace('#^(.*)\\\(.*?)$#i', '${1}\\\Table\\\${2}', get_called_class()) . 'Table';
    }

    public function isNew()
    {
        return (boolean) !($this->status & \Quartz\Quartz::EXIST);
    }

    public function isModified()
    {
        return (boolean) ($this->status & \Quartz\Quartz::MODIFIED);
    }

    public function setNew($boolean)
    {
        $this->isNew = $boolean;
        if ($boolean)
        {
            $this->status = $this->status & (0xff ^ \Quartz\Quartz::EXIST);
        } else
        {
            $this->status = $this->status | \Quartz\Quartz::EXIST;
        }
        return $this;
    }

    public function setModified($boolean)
    {
        if ($boolean)
        {
            $this->status = $this->status | \Quartz\Quartz::MODIFIED;
        } else
        {
            $this->status = $this->status & (0xff ^ \Quartz\Quartz::MODIFIED);
        }
        return $this;
    }

    public function exists()
    {
        return (boolean) ($this->status & \Quartz\Quartz::EXIST);
    }

    public function __clone()
    {
        $this->setNew(true);
        $this->exists = false;
    }

    public function getOrm()
    {
        return $this->getTable()->getOrm();
    }

    public function hasChanged()
    {
        return $this->isModified();
    }

    /**
     *
     * @return Table
     */
    public function &getTable(\Quartz\Connection\Connection $conn = null)
    {
        $className = get_called_class();
        return \Quartz\Quartz::getInstance()->getTable($className);
    }

    /**
     *
     * @return array
     */
    public function getValuesUpdated()
    {
        return $this->valuesUpdated;
    }

    /**
     *
     * @return array
     */
    public function getPreviousValues()
    {
        return $this->oldValues;
    }

    /**
     * 
     * @param string $property
     * @return boolean
     */
    public function hasPropertyChanged($property)
    {
        return array_key_exists($property, $this->valuesUpdated);
    }

    /**
     * 
     * @param string $property
     * @return mixed
     */
    public function getPreviousValue($property)
    {
        return array_key_exists($property, $this->oldValues) ? $this->oldValues[$property] : null;
    }

    public function getGetter($property)
    {
        $property = preg_replace_callback('#(.)_(.)#', function($m)
        {
            return $m[1] . ucfirst($m[2]);
        }, $property);
        return 'get' . ucfirst($property);
    }

    public function getSetter($property)
    {
        $property = preg_replace_callback('#(.)_(.)#', function($m)
        {
            return $m[1] . ucfirst($m[2]);
        }, $property);
        return 'set' . ucfirst($property);
    }

    /**
     *
     * @param string $property
     * @return mixed
     */
    public function get($property)
    {
        $property = $this->getTable()->getRealPropertyName($property);
        return $this->values[$property];
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exception\NotExistsException
     */
    public function set($property, $value)
    {
        $property = $this->getTable()->getRealPropertyName($property);

        $newValue = $this->getTable()->convertPropertyValueToDb($property, $value);
        $oldValue = $this->getTable()->convertPropertyValueToDb($property, $this->values[$property]);

        if ($newValue !== $oldValue)
        {
            if (!array_key_exists($property, $this->oldValues))
            {
                $this->oldValues[$property] = $this->values[$property];
            }
            $this->values[$property] = $value;
            $this->valuesUpdated[$property] = $value;

            $this->setModified(true);
        }
        return $this;
    }

    /**
     *
     * @param string $property
     * @return boolean
     */
    public function has($property)
    {
        return $this->getTable()->hasProperty($property);
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exception\NotExistsException
     */
    public function addTo($property, $value)
    {
        $property = $this->getTable()->getRealPropertyName($property);
        $type = $this->getTable()->getPropertyType($property);
        if ($type == 'array' || preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $type))
        {
            if (is_null($this->values[$property]))
            {
                $this->values[$property] = array();
            }

            if (!in_array($value, $this->values[$property]))
            {
                $this->oldValues[$property] = $this->values[$property];
                $this->values[$property][] = $value;
                $this->valuesUpdated[$property] = $this->values[$property];
                $this->status = $this->status | \Quartz\Quartz::MODIFIED;
            }
        }

        return $this;
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exception\NotExistsException
     */
    public function removeFrom($property, $value)
    {
        $property = $this->getTable()->getRealPropertyName($property);

        if ($this->getTable()->getPropertyType($property) == 'array')
        {
            if (in_array($value, $this->values[$property]))
            {
                if (!array_key_exists($property, $this->oldValues))
                {
                    $this->oldValues[$property] = $this->values[$property];
                }

                $new = array();
                foreach ($this->values[$property] as $elt)
                {
                    if ($elt != $value)
                    {
                        $new[] = $elt;
                    }
                }

                $this->values[$property] = $new;
                $this->valuesUpdated[$property] = $this->values[$property];

                $this->status = $this->status | \Quartz\Quartz::MODIFIED;
            }
        }

        return $this;
    }

    /**
     * Add one object to a ManyToOne relation in order to update it during the
     * save action
     *
     * @param string $relation
     * @param Entity $object
     * @return \Quartz\Object\Entity
     * @throws \Exception
     */
    public function setOneRelation($relation, Entity $object = null)
    {
        if (is_null($object))
        {
            $this->setObjectRelation($relation, $object, true);
            return $this;
        }

        if ($this->getTable()->hasOneRelation($relation))
        {
            if (!isset($this->objectsPreSave[$relation]))
            {
                $this->objectsPreSave[$relation] = array();
            }

            $config = $this->getTable()->getOneRelation($relation);
            $class = $config['class'];
            if (!$object instanceof $class)
            {
                throw new \Exception('In ' . $this->getTable()->getName() . ' manyRelation ' . $relation . ' must be set with ' . $class . ' and not with ' . (is_object($object) ? get_class($object) : gettype($object)));
            }

            $this->objectsPreSave[$relation][0] = $object;
            $this->setObjectRelation($relation, $object, true);
        }
        return $this;
    }
    
    protected function unlinkRelation($relation, \Quartz\Connection\Connection $connection, Table $table, $foreignKey, $localValue) {
        $query = array(
            $foreignKey => $localValue
        );

        $connection->delete($table, $query);
    }

    /**
     * Add objects to a oneToMany relation in order to update them during
     * the save action
     *
     * @param string $relation
     * @param array $objects
     * @return \Quartz\Object\Entity
     * @throws \Exception
     */
    protected function setManyRelation($relation, array $objects = null)
    {
        if (is_null($objects))
        {
            $this->setObjectRelation($relation, null, false);
            return $this;
        }

        if ($this->getTable()->hasManyRelation($relation))
        {
            $this->objectsPostSave[$relation] = array();
            $this->objectsLinked[$relation] = array(); // reset linked objects

            $config = $this->getTable()->getManyRelation($relation);
            $class = $config['class'];

            foreach ($objects as $object)
            {
                if (is_null($objects))
                {
                    continue;
                }
                if (!$object instanceof $class)
                {
                    throw new \Exception('In ' . $this->getTable()->getName() . ' manyRelation ' . $relation . ' must be set with ' . $class . ' and not with ' . get_class($object));
                }

                $this->objectsPostSave[$relation][] = $object;
                $this->setObjectRelation($relation, $object, false);
            }
        }
        return $this;
    }

    /**
     * Return the objects linked with $this or if it the first "get"
     * retrieve from DB
     *
     * @param string $relation
     * @return array|null
     */
    public function getRelation($relation)
    {
        if ($this->getTable()->hasOneRelation($relation))
        {
            if (!array_key_exists($relation, $this->objectsLinked))// || is_null($this->objectsLinked[$relation]))
            {
                $this->objectsLinked[$relation] = $this->findRelation($relation, true);
            }
            return $this->objectsLinked[$relation];
        } else if ($this->getTable()->hasManyRelation($relation))
        {
            if (!array_key_exists($relation, $this->objectsLinked))
            {
                $res = $this->findRelation($relation, false);
                $this->objectsLinked[$relation] = $res ? $res : array();
            }

            return $this->objectsLinked[$relation];
        }
        return null;
    }

    /**
     *
     * @param string $methodName
     * @param array $args
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exception\NotExistsException
     * @throws \Exception
     */
    public function __call($methodName, $args)
    {
        if (preg_match('~^(set|get)([_A-Z])(.*)$~', $methodName, $matches))
        {
            $function = $matches[1];

            try
            {
                $property = $this->getTable()->getRealPropertyName($matches[2] . $matches[3]);
            } catch (\Quartz\Exception\NotExistsException $e)
            {
                $property = $matches[2] . $matches[3];
                $check = array(
                    strtolower($property),
                    $property,
                    strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $property)),
                    preg_replace('/(.)([A-Z])/', '$1_$2', $property),
                );
                foreach ($check as $property)
                {
                    if ($this->getTable()->hasOneRelation($property))
                    {
                        if ($function == 'get')
                        {
                            return $this->getRelation($property);
                        } else
                        {
                            $this->setOneRelation($property, $args[0]);
                            return $this;
                        }
                    } else if ($this->getTable()->hasManyRelation($property))
                    {
                        if ($function == 'get')
                        {
                            return $this->getRelation($property);
                        } else
                        {
                            $this->setManyRelation($property, is_array($args[0]) ? $args[0] : $args );
                            return $this;
                        }
                    }
                }
                throw $e;
            }
            switch ($function)
            {
                case 'set':
                    return $this->set($property, array_key_exists(0, $args) ? $args[0] : null);
                case 'get':
                    return $this->get($property);
            }
        } else if (preg_match('~^(addTo|removeFrom)([_A-Z])(.*)$~', $methodName, $matches))
        {
            $function = $matches[1];
            try
            {
                $property = $this->getTable()->getRealPropertyName($matches[2] . $matches[3]);
                switch ($function)
                {
                    case 'addTo':
                        return $this->addTo($property, $args[0]);
                    case 'removeFrom':
                        return $this->removeFrom($property, $args[0]);
                    default:
                        throw new \Quartz\Exception\NotExistsException('In ' . $this->getTable()->getName() . ' method ' . $methodName);
                }
            } catch (\Quartz\Exception\NotExistsException $e)
            {
                throw $e;
            }
        }

        throw new \Exception($methodName . ' not implemented in ' . get_class($this));
    }

    /**
     * Retrieve linked object from DB
     *
     * @param string $relation
     * @param boolean $one
     * @return Entity|array|null
     */
    protected function findRelation($relation, $one = true)
    {
        if ($one)
        {
            $config = $this->getTable()->getOneRelation($relation);
        } else
        {
            $config = $this->getTable()->getManyRelation($relation);
        }

        $local = $config['local'];
        $foreign = $config['foreign'];
        $class = $config['class'];

        if ($this->get($local) != null)
        {
            $objs = \Quartz\Quartz::getInstance()->getTable($class)->find(array($foreign => $this->get($local)), $config['orderBy'], $config['limit'], $config['offset']);

            if ($one)
            {
                if (!$objs->isEmpty())
                {
                    $obj = $objs->current();
                    if ($obj)
                    {
                        return $obj;
                    }
                }
            } else
            {
                return $objs;
            }
        }
        return null;
    }

    /**
     * Set the foreignKey of the related objects
     * or set the local key with the value of the foreign key
     * @param string $relation
     * @param Entity $object
     * @return \Quartz\Object\Entity
     */
    protected function setForeignKey($relation, Entity &$object = null)
    {
        if ($this->getTable()->hasOneRelation($relation))
        {
            $config = $this->getTable()->getOneRelation($relation);

            $local = $config['local'];
            $foreign = $config['foreign'];
            $class = $config['class'];
            $value = is_null($object) ? null : $object->get($foreign);
            $this->set($local, $value);
        } else if ($this->getTable()->hasManyRelation($relation))
        {
            if (null === $object)
            {
                return $this;
            }

            $config = $this->getTable()->getManyRelation($relation);
            $local = $config['local'];
            $foreign = $config['foreign'];
            $class = $config['class'];
            $value = $this->get($local);
            $object->set($foreign, $value);
        }
        return $this;
    }

    /**
     *
     * @param string $relation
     * @param Entity $object
     * @return \Quartz\Object\Entity
     */
    protected function setObjectRelation($relation, Entity $object = null, $one = true)
    {
        if ($one)
        {
            $this->objectsLinked[$relation] = $object;
        } else
        {
            if (!isset($this->objectsLinked[$relation]))
            {
                $this->objectsLinked[$relation] = array();
            }
            if (null !== $object)
            {
                $this->objectsLinked[$relation][] = $object;
            }
        }
        return $this;
    }

    public function hydrate($values)
    {
        foreach ($values as $key => $value)
        {
            $this->set($key, $value);
        }
        $this->setNew(false);
        $this->setModified(false);
        $this->oldValues = array();
        $this->valuesUpdated = array();
    }

    /**
     *
     * @return \Quartz\Object\Entity
     */
    protected function preSave()
    {
        foreach ($this->objectsPreSave as $relation => $objects)
        {
            foreach ($objects as $object)
            {
                $object->save();
                $this->setForeignKey($relation, $object);
            }
        }

        $this->objectsPreSave = array();
        return $this;
    }

    /**
     *
     * @return \Quartz\Object\Entity
     */
    public function save()
    {
        $this->preSave();
        $this->getTable()->save($this);
        $this->postSave();

        $this->setNew(false);
        $this->valuesUpdated = array();
        $this->exists = true;

        return $this;
    }

    public function delete()
    {
        $this->getTable()->delete($this);
        $this->status = \Quartz\Quartz::NONE;
        $this->valuesUpdated = array();
        return true;
    }

    /**
     *
     * @return \Quartz\Object\Entity
     */
    public function postSave()
    {
        foreach ($this->objectsPostSave as $relation => $objects)
        {
            $conn = $this->getTable()->getConnection();

            if ($this->getTable()->hasOneRelation($relation))
            {
                $config = $this->getTable()->getOneRelation($relation);
            } else
            {
                $config = $this->getTable()->getManyRelation($relation);
            }

            $local = $config['local'];
            $foreign = $config['foreign'];
            $class = $config['class'];

            $table = $this->getOrm()->getTable($class);
            $this->unlinkRelation($relation, $conn, $table, $foreign, $this->get($local));
            foreach ($objects as $object)
            {
                $this->setForeignKey($relation, $object);
                $object->save();
            }
        }

        $this->objectsPostSave = array();
        return $this;
    }

    /**
     *
     * @param string|int $name
     * @return boolean
     */
    public function offsetExists($name)
    {
        return $this->getTable()->hasProperty($name);
    }

    /**
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function offsetGet($name)
    {
        if (!$this->getTable()->hasProperty($name))
        {
            if (!$this->getTable()->hasOneRelation($name))
            {
                if (!$this->getTable()->hasManyRelation($name))
                {
                    throw new \InvalidArgumentException(sprintf('In %s "%s" does not exist.', $this->className, $name));
                } else
                {
                    return $this->getRelation($name, false);
                }
            } else
            {
                return $this->getRelation($name, true);
            }
        }

        return $this->get($name);
    }

    /**
     *
     * @param string|int $offset
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if (!$this->getTable()->hasProperty($offset))
        {
            throw new \InvalidArgumentException(sprintf('In %s "%s" does not exist.', $this->className, $offset));
        }

        $this->set($offset, $value);
    }

    /**
     *
     * @param string $offset
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Cannot unset fields.');
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this->getTable()->getProperties() as $property)
        {
            $result[$property] = $this->get($property);
        }
        return $result;
    }

}
