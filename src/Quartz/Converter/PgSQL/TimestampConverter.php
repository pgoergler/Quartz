<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of TimestampConverter
 *
 * @author paul
 */
class TimestampConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === 'NULL' || $data === 'null' || $data === null || $data === '')
        {
            return null;
        }

        $tz = ( $typeParameter && is_string($typeParameter)) ? new \DateTimeZone($typeParameter) : null;
        if (!is_null($type))
        {
            return new \DateTime(preg_replace("#^$type '(.*?)'$#", '$1', $data), $tz);
        }
        return new \DateTime($data, $tz);
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        $tz = ( $typeParameter && is_string($typeParameter)) ? new \DateTimeZone($typeParameter) : null;
        if (!$data instanceof \DateTime)
        {
            if (is_numeric($data))
            {
                $data = new \DateTime('@' . $data, $tz);
            } else
            {
                $data = new \DateTime($data, $tz);
            }
        }

        if( $tz )
        {
            return sprintf("%s '%s'", 'timestamp', $data->setTimezone($tz)->format('Y-m-d H:i:s.u'));
        }
        return sprintf("%s '%s'", 'timestamp', $data->format('Y-m-d H:i:s.u'));
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "timestamp without time zone$array";
    }

}
