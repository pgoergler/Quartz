<?php

namespace Quartz\Converter\PgSQL84;

/**
 * Description of HStoreConverter
 *
 * @author paul
 */
class HStoreConverter implements \Quartz\Converter\ConverterInterface
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
            throw new \Exception(sprintf("HStore::toDb takes an associative array as parameter ('%s' given).", gettype($data)));
        }

        $insert_values = array();

        foreach ($data as $key => $value)
        {
            if (is_null($value))
            {
                $insert_values[] = sprintf('"%s" => NULL', $key);
            } elseif (is_array($value))
            {
                $insert_values[] = sprintf('"%s" => "%s"', $key, str_replace("'", "''", str_replace('"', '\\\\"', json_encode($value))));
            } else
            {
                $insert_values[] = sprintf('"%s" => "%s"', $key, str_replace("'", "''", str_replace('"', '\\\\"', $value)));
            }
        }

        return sprintf("'%s'::hstore", join(', ', $insert_values));
    }

    public function translate($type)
    {
        return 'hstore';
    }
}
