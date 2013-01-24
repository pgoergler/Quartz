<?php

namespace Quartz\Converter\Common;

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
                $json = json_encode($data);
                if( $json !== false )
                {
                    return $json;
                }
            }

            throw new \InvalidArgumentException(sprintf("Json converter toDb() data must be an array ('%s' given).", gettype($data)));
        }
        return "'" . preg_replace("#'#", "\\'", json_encode($data)) . "'";
    }

}

?>
