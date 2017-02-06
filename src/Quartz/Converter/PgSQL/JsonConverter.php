<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of ArrayConverter
 *
 * @author paul
 */
class JsonConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
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

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($type))
        {
            throw new \InvalidArgumentException(sprintf('Json converter must be given a type.'));
        }
        if (!is_array($data))
        {
            if (is_null($data))
            {
                return 'NULL';
            } else
            {
                return $this->encodeJson($data);
            }

            throw new \InvalidArgumentException(sprintf("Json converter toDb() data must be an array ('%s' given).", gettype($data)));
        }
        return $this->encodeJson($data);
    }

    public function encodeJson($data)
    {
        $json = json_encode($data);
        if ($json === false)
        {
            throw new \InvalidArgumentException(sprintf("Json converter toDb() data must be an array ('%s' given).", gettype($data)));
        }

        $escaped = false;
        $jsonData = json_encode($data);
        $jsonData = str_replace("'", "''", $jsonData);
        $jsonData = str_replace('\\', '\\\\', $jsonData);
        return sprintf("E'%s'", $jsonData);
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "json$array";
    }

}
