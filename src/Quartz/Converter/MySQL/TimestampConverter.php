<?php

namespace Quartz\Converter\MySQL;

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
        if (!is_null($type))
        {
            return new \DateTime(preg_replace('#^\'(.*?)\'$#', '$1', $data));
        }
        return new \DateTime($data);
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

        if ($tz)
        {
            return sprintf("%s '%s'", 'timestamp', $data->setTimezone($tz)->format('Y-m-d H:i:s.u'));
        }
        return sprintf("%s '%s'", 'timestamp', $data->format('Y-m-d H:i:s.u'));
    }

    public function translate($type, $parameter)
    {
        return "DATETIME";
    }

}
