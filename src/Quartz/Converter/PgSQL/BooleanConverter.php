<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of BooleanConverter
 *
 * @author paul
 */
class BooleanConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === 'NULL' || $data === 'null' || $data === null)
        {
            return null;
        }
        return is_int($data) ? ($data ? true : false) : in_array($data, array('t', 'T', 'true', 'TRUE', 'yes', 'YES', 1, 'on', 'ON'));
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return 'NULL';
        }
        if (is_int($data))
        {
            return $data ? 'true' : 'false';
        } else
        {
            return in_array($data, array('t', 'T', 'true', 'TRUE', 'yes', 'YES', 1, 'on', 'ON')) ? 'true' : 'false';
        }
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "boolean$array";
    }

}
