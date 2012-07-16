<?php

namespace Quartz\Converter\Common;

/**
 * Description of NumberConverter
 *
 * @author paul
 */
class NumberConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
    {
        if( $data == 'NULL' )
        {
            return null;
        }
        return intval($data);
    }

    public function toDb($data, $type = null)
    {
        return is_null($data) ? 'NULL' : $data;
    }

}

?>
