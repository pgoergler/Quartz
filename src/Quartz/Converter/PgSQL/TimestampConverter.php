<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of TimestampConverter
 *
 * @author paul
 */
class TimestampConverter implements \Quartz\Converter\Converter
{

    protected function isNullFromDb($data)
    {
        return ($data === 'NULL' || $data === 'null' || $data === null || $data === '');
    }

    protected function buildDatetime($data, $timezone = null)
    {
        if (is_numeric($data))
        {
            return new \DateTime('@' . $data, $timezone);
        } elseif (is_array($data) && array_key_exists('date', $data) && array_key_exists('timezone', $data))
        {
            return new \DateTime($data['date'], new \DateTimeZone($data['timezone']));
        } else
        {
            return new \DateTime($data, $timezone);
        }
    }

    public function fromDb($data, $type, $typeParameter)
    {
        if ($this->isNullFromDb($data))
        {
            return null;
        }

        $tz = ( $typeParameter && is_string($typeParameter)) ? new \DateTimeZone($typeParameter) : null;
        if (!is_null($type))
        {
            return new \DateTime(preg_replace("#^timestamp (tz|with time zone|without time zone)? '(.*?)'$#", '$2', $data), $tz);
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
            $data = $this->buildDatetime($data, $tz);
        }

        if ($tz)
        {
            return $this->translateToDb($data->setTimezone($tz));
        }
        return $this->translateToDb($data);
    }

    protected function translateToDb($data)
    {
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
