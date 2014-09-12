<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of StringConverter
 *
 * @author paul
 */
class StringConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type = null)
    {
        if (is_null($data))
        {
            return null;
        }
        return preg_replace('#([\'"])(.*?)([\'"])$#i', '$2', $data);
    }

    public function toDb($data, $type = null)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        if (is_bool($data))
        {
            return ($data === true) ? "'true'" : "'false'";
        }
        //return "'" . str_replace('\\"', '"', $data) . "'";

        $data = str_replace("'", "''", $data);
        $data = str_replace('\\', '\\\\', $data);
        return sprintf("'%s'", $data);
    }

    public function translate($type)
    {
        if( $type == 'text ')
        {
            return 'text';
        }

        $size = '';
        if (preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
        {
            $size = '(' . $matchs[2] . ')';
        }
        return "character varying$size";
    }
}
