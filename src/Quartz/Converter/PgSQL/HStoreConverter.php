<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of HStoreConverter
 *
 * @author paul
 */
class HStoreConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type = null)
    {
        if ($data == 'NULL')
        {
            return null;
        }

        if ($data == "''::hstore")
        {
            return array();
        }

        @eval(sprintf("\$hstore = array(%s);", $data));
        if (!(isset($hstore) and is_array($hstore)))
        {
            return null;
        }

        return $hstore;
    }

    public function toDb($data, $type = null)
    {
        if (is_null($data) || $data === '')
        {
            return "''::hstore";
        }

        if (!is_array($data))
        {
            throw new \Exception(sprintf("HStore::toDb takes an associative array as parameter ('%s::%s' given).", $data, gettype($data)));
        }

        $insert_values = array();

        foreach ($data as $key => $value)
        {
            if (is_null($value))
            {
                $insert_values[] = sprintf('"%s" => NULL', $key);
            } elseif (is_array($value))
            {
                $insert_values[] = sprintf('"%s" => "%s"',  addcslashes($key, '\"'), addcslashes(json_encode($value), '\"'));
            } elseif (is_object($value))
            {
                $insert_values[] = sprintf('"%s" => "%s"',  addcslashes($key, '\"'), addcslashes(json_encode($value), '\"'));
            } else
            {
                $insert_values[] = sprintf('"%s" => "%s"',  addcslashes($key, '\"'), addcslashes($value, '\"'));
            }
        }

        return sprintf("%s(\$hst\$%s\$hst\$)", $type, join(', ', $insert_values));
    }

    public function translate($type)
    {
        return 'hstore';
    }
}