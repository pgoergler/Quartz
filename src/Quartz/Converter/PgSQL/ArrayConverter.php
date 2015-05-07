<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of ArrayConverter
 *
 * @author paul
 */
class ArrayConverter implements \Quartz\Converter\Converter
{

    protected $connection;

    /**
     *
     * @param \Quartz\Connection\Connection $connection
     */
    public function __construct(\Quartz\Connection\Connection $connection)
    {
        $this->connection = $connection;
    }

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === null || $data == 'NULL')
        {
            return null;
        }

        if (preg_match('#^ARRAY\[\]::.*$#', $data))
        {
            return array();
        }

        if (is_null($type))
        {
            throw new \Exception(sprintf('Array converter must be given a type.'));
        }

        if ($data !== "{NULL}" && $data !== "{}")
        {
            $array = \str_getcsv(\trim($data, "{}"));
            $arraySize = is_array($typeParameter) ? $typeParameter['size'] : $typeParameter;
            if ($arraySize > 0)
            {
                $array = \array_slice($array, 0, $arraySize);
            }

            $fn = is_array($typeParameter) && isset($typeParameter['converter']) ? $typeParameter['converter'] : false;
            if (is_callable($fn))
            {
                
            } else
            {
                \Logging\LoggersManager::getInstance()->get()->error('converter not set in {0} ? {1}', [$typeParameter, $fn]);
                $converter = $this->connection->getConverterForType($type);
                $fn = function($value) use(&$converter, $type)
                {
                    return $converter->fromDb($value, $type, null);
                };
            }
            return array_map(function($value) use(&$fn)
            {
                $valueUnescaped = str_replace(array('\\\\', '\\"'), array('\\', '"'), $value);
                return $value === "NULL" ? null : call_user_func($fn, $valueUnescaped);
            }, $array);
        } else
        {
            return array();
        }
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($type))
        {
            throw new \Exception(sprintf('Array converter must be given a type.'));
        }

        if (!is_array($data))
        {
            if (is_null($data))
            {
                return 'NULL';
            }

            throw new \Exception(sprintf("Array converter toPg() data must be an array ('%s' given).", gettype($data)));
        }

        $arraySize = is_array($typeParameter) ? $typeParameter['size'] : $typeParameter;
        $array = $data;
        if ($arraySize > 0)
        {
            $array = \array_slice($data, 0, $arraySize);
        }

        $fn = is_array($typeParameter) && isset($typeParameter['converter']);
        if (!is_callable($fn))
        {
            $converter = $this->connection->getConverterForType($type);
            $fn = function($value) use(&$converter, $type)
            {
                return $converter->toDb($value, $type, null);
            };
        }
        \array_walk($array, function($value) use(&$fn)
        {

            if (is_null($value))
            {
                return 'NULL';
            }

            return call_user_func($fn, $value);
        });

        return sprintf('ARRAY[%s]::%s[]', join(',', $array), $type);
    }

    public function translate($type, $parameter)
    {
        $array = '[]';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "array$array";
    }

}
