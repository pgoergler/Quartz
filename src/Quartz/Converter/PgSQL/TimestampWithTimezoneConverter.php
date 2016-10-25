<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of TimestampWithTimezoneConverter
 *
 * @author paul
 */
class TimestampWithTimezoneConverter extends TimestampConverter
{
    
    protected function translateToDb($data)
    {
        return sprintf("%s '%s'", 'timestamp with time zone', $data->format('Y-m-d H:i:s.uP'));
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "timestamp with time zone$array";
    }

}
