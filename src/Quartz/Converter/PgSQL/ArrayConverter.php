<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of ArrayConverter
 *
 * @author paul
 */
class ArrayConverter implements \Quartz\Converter\ConverterInterface
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

    public function fromDb($data, $type = null)
    {
        if ($data === null || $data == 'NULL')
        {
            return null;
        }

        if (is_null($type))
        {
            throw new \Exception(sprintf('Array converter must be given a type.'));
        }

        if ($data !== "{NULL}" and $data !== "{}")
        {
            $converter = $this->connection->getConverterForType($type);

            return array_map(function($val) use (&$converter, $type)
                            {
                                return $val !== "NULL" ? $converter->fromDb(str_replace('\\"', '"', $val), $type) : null;
                            }, str_getcsv(str_replace('\\\\', '\\', trim($data, "{}"))));
        } else
        {
            return array();
        }
    }

    public function toDb($data, $type = null)
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

        $converter = $this->connection
                ->getConverterForType($type);

        return sprintf('ARRAY[%s]::%s[]', join(',', array_map(function ($val) use ($converter, $type)
                                        {
                                            return !is_null($val) ? $converter->toDb($val, $type) : 'NULL';
                                        }, $data)), $type);
    }

    public function translate($type)
    {
        return 'array[]';
    }

}
