<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of TimeWithTimezoneConverter
 *
 * @author paul
 */
class TimeWithTimezoneConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === 'NULL' || $data === 'null' || $data === null || $data === '')
        {
            return null;
        }
        if (!is_null($type))
        {
            return new \DateTime(preg_replace('#^time (tz|with time zone) \'(.*?)\'$#', '$1', $data));
        }
        return new \DateTime($data);
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        if (!$data instanceof \DateTime)
        {
            if (is_numeric($data))
            {
                $data = new \DateTime('@' . $data);
            } else
            {
                $data = new \DateTime($data);
            }
        }

        return sprintf("%s '%s'", 'time with time zone', $data->format('H:i:s.uP'));
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "time with time zone$array";
    }

}
