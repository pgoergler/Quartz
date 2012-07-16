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
        if( $data == 'NULL' )
        {
            return null;
        }
        
        if (is_null($type))
        {
            throw new \Exception(sprintf('Json converter must be given a type.'));
        }

        if ($data !== "{NULL}" and $data !== "{}" and $data !== "[]")
        {
            return json_decode($data, true);
        } else
        {
            return array();
        }
    }

    public function toDb($data, $type = null)
    {
        if (is_null($type))
        {
            throw new \Exception(sprintf('Json converter must be given a type.'));
        }
        if (!is_array($data))
        {
            if (is_null($data))
            {
                return 'NULL';
            }

            throw new \Exception(sprintf("Json converter toDb() data must be an array ('%s' given).", gettype($data)));
        }
        return json_encode($data);
    }

}

?>
