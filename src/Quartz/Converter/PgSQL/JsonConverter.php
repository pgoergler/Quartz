<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of ArrayConverter
 *
 * @author paul
 */
class JsonConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
    {
        if ($data == 'NULL')
        {
            return null;
        }

        if (is_null($type))
        {
            throw new \Exception(sprintf('Json converter must be given a type.'));
        }

        if ($data !== "{NULL}" and $data !== "{}" and $data !== "[]")
        {
            $json = json_decode($data, true);
            return $json;
        } else
        {
            return array();
        }
    }

    public function toDb($data, $type = null)
    {
        $data = pg_escape_string(json_encode($data));
        $type = is_null($type) ? '' : sprintf("%s ", $type);
        $data = sprintf("%s'%s'",  $type, $data);

        return $data;
    }

    public function translate($type)
    {
        return 'json';
    }
}
