<?php

namespace Quartz\Object;

/**
 * Description of Entity
 *
 * @author paul
 */
abstract class Entity implements \ArrayAccess, \IteratorAggregate
{

    protected $className = null;
    protected $table = null;
    protected $values = array();
    protected $oldValues = array();
    protected $valuesUpdated = array();
    protected $isNew = true;
    protected $exists = false;
    protected $objectsLinked = array();
    protected $objectsPreSave = array();
    protected $objectsPostSave = array();

    public function __construct(\Quartz\Connection\Connection $conn = null)
    {
        $this->className = get_called_class();

        $values = $this->getTable($conn);

        foreach ($this->getTable($conn)->getProperties() as $property)
        {
            $this->values[$property] = $this->getTable()->getDefaultValue($property);
        }
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function setNew($boolean)
    {
        $this->isNew = $boolean;
        return $this;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function __clone()
    {
        $this->setNew(true);
        //$this->values = clone $this->values;
        $this->exists = false;
    }

    public function hasChanged()
    {
        return $this->isNew == true || count($this->valuesUpdated) > 0;
    }

    /**
     *
     * @return Table
     */
    public function &getTable(\Quartz\Connection\Connection $conn = null)
    {
        if ($this->table == null)
        {
            if (\Quartz\Quartz::getInstance()->hasTable($this->className))
            {
                $this->table = \Quartz\Quartz::getInstance()->getTable($this->className);
            } else
            {
                $tableName = static::getTableClassName();
                $this->table = new $tableName($conn);
                $this->table->setObjectClassName($this->className);

                \Quartz\Quartz::getInstance()->setTable($this->className, $this->table);
            }
        }
        return $this->table;
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
     * @return string
     */
    public static function getTableClassName()
    {
        $class = get_called_class();

        if (!isset($class::$tableClassName) || is_null($class::$tableClassName))
        {
            return preg_replace('#^(.*)\\\(.*?)$#i', '${1}\\\Table\\\${2}', $class) . 'Table';
        }
        return $class::$tableClassName;
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
        return $this->getTable()->hasProperty($property) ? $this->values[$property] : $this->table->getDefaultValue($property);
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exceptions\NotExistsException
     */
    public function set($property, $value)
    {
        $property = $this->getTable()->getRealPropertyName($property);
        if ($value !== $this->values[$property])
        {
            $this->oldValues[$property] = $this->values[$property];
            $this->values[$property] = $value;
            $this->valuesUpdated[$property] = $value;
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
     * @throws \Quartz\Exceptions\NotExistsException
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
            }
        }

        return $this;
    }

    /**
     *
     * @param string $property
     * @param mixed $value
     * @return \Quartz\Object\Entity
     * @throws \Quartz\Exceptions\NotExistsException
     */
    public function removeFrom($property, $value)
    {
        $property = $this->getTable()->getRealPropertyName($property);

        if ($this->getTable()->getPropertyType($property) == 'array')
        {
            if (in_array($value, $this->values[$property]))
            {
                $this->oldValues[$property] = $this->values[$property];

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
     * @throws \RuntimeException
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
                throw new \RuntimeException('In ' . $this->getTable()->getName() . ' manyRelation ' . $relation . ' must be set with ' . $class . ' and not with ' . get_class($object));
            }

            $this->objectsPreSave[$relation][0] = $object;
            $this->setObjectRelation($relation, $object, true);
        }
        return $this;
    }

    /**
     * Add objects to a oneToMany relation in order to update them during
     * the save action
     *
     * @param string $relation
     * @param array $objects
     * @return \Quartz\Object\Entity
     * @throws \RuntimeException
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
            if (!isset($this->objectsPostSave[$relation]))
            {
                $this->objectsPostSave[$relation] = array();
            }

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
                    throw new \RuntimeException('In ' . $this->getTable()->getName() . ' manyRelation ' . $relation . ' must be set with ' . $class . ' and not with ' . get_class($object));
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
     * @throws \Quartz\Exceptions\NotExistsException
     * @throws \RuntimeException
     */
    public function __call($methodName, $args)
    {
        if (preg_match('~^(set|get)([_A-Z])(.*)$~', $methodName, $matches))
        {
            $function = $matches[1];

            try
            {
                $property = $this->getTable()->getRealPropertyName($matches[2] . $matches[3]);
            } catch (\Quartz\Exceptions\NotExistsException $e)
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
                        //Logger::getRootLogger()->trace($this->className . ' has single relation with ' . $property);

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
                        //Logger::getRootLogger()->trace($this->className . ' has many relation with ' . $property);
                        if ($function == 'get')
                        {
                            return $this->getRelation($property);
                            //return $this->getTable()->findHasSingleRelation($property, $this, false);
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
                        throw new \Quartz\Exceptions\NotExistsException('In ' . $this->getTable()->getName() . ' method ' . $methodName);
                }
            } catch (\Quartz\Exceptions\NotExistsException $e)
            {
                throw $e;
            }
        }

        throw new \RuntimeException($methodName . ' not implemented in ' . get_class($this));
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

        //Logger::getRootLogger()->trace("$class::findBy" . ucfirst($foreign) . "(\$object->$getter()) one?" . ($one ? 'true' : 'false'));

        if ($this->get($local) != null)
        {
            $fct = 'findBy' . ucfirst($foreign);
            //$objs = \Quartz\Quartz::getInstance()->getTable($class)->$fct($this->get($local));
            $objs = \Quartz\Quartz::getInstance()->getTable($class)->find(array($foreign => $this->get($local)), $config['orderBy'], $config['limit'], $config['offset']);

            if ($one)
            {
                $obj = array_shift($objs);
                if ($obj)
                {
                    return $obj;
                }
                //Logger::getRootLogger()->trace("no association found");
            } else
            {
                return $objs;
            }
        } else
        {
            //Logger::getRootLogger()->trace("link is null");
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
            $config = $this->getTable()->getManyRelation($relation);

            $local = $config['local'];
            $foreign = $config['foreign'];
            $class = $config['class'];
            $value = is_null($object) ? null : $object->get($local);
            $this->set($foreign, $value);
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
            $this->objectsLinked[$relation][] = $object;
        }
        return $this;
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

        $conn = $this->getTable()->getConnection();
        $primaryKeys = $this->getTable()->getPrimaryKeys();

        if ($this->isNew())
        {
            foreach ($this->values as $key => $value)
            {
                if ($this->getTable()->getPropertyType($key) == 'sequence' && is_null($value))
                {
                    try
                    {
                        $this->values[$key] = $this->getTable()->getConnection()->getSequence($this->getTable(), $key);
                    } catch (\RuntimeException $e)
                    {
                        $this->values[$key] = null;
                    }
                }
            }

            $newObj = $this->getTable()->convertFromDb($conn->insert($this->getTable(), $this->getTable()->convertToDb($this)));
            foreach ($primaryKeys as $property)
            {
                $this->set($property, $newObj[$property]);
            }
            $this->valuesUpdated = array();
        } else
        {
            if (count($this->getValuesUpdated()) > 0)
            {
                $pKey = $this->getTable()->getPrimaryKeys();
                $query = array();
                foreach ($pKey as $pk)
                {
                    $query[$pk] = $this->get($pk);
                }

                $values = $this->getTable()->convertToDb($this->getValuesUpdated());

                $this->valuesUpdated = array();

                $conn->update($this->getTable()->getName(), $query, $values);

                //$conn->updateEntity($this);
            }
        }

        $this->postSave();

        $this->setNew(false);
        $this->exists = true;

        return $this;
    }

    public function delete()
    {
        $conn = $this->getTable()->getConnection();
        $conn->deleteEntity($this);
        $this->exists = false;
        $this->setNew(true);
        $this->valuesUpdated = array();
        return true;
    }

    public function hydrate($row)
    {
        foreach ($this->getTable()->getProperties() as $property)
        {
            //$setter = $this->getSetter($property);
            //$setter = 'set';
            if (!array_key_exists($property, $row))
            {
                $this->set($property, $this->getTable()->getDefaultValue($property));
            } else
            {
                //$nvalue = $this->getTable()->checkPropertyValue($property, $row[$property]);

                $this->set($property, $row[$property]);
            }
        }
        $this->valuesUpdated = array();
        return $this;
    }

    /**
     *
     * @return \Quartz\Object\Entity
     */
    public function postSave()
    {
        foreach ($this->objectsPostSave as $relation => $objects)
        {
            $o = $objects[0];

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


            //$getter = $this->getGetter($local);
            $query = array(
                $foreign => $this->get($local),
            );
            $conn->delete($o->getTable(), $query);
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
                    return $this->getRelation($property, false);
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