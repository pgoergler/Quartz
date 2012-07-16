<?php

namespace Quartz\Converter\MySQL;

/**
 * Description of TimestampConverter
 *
 * @author paul
 */
class TimestampConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
    {
        if( $data == 'NULL' )
        {
            return null;
        }
        return new \DateTime($data);
    }

    public function toDb($data, $type = null)
    {
        if( is_null($data) )
        {
            return 'NULL';
        }
        if (!$data instanceof \DateTime)
        {
            if(is_numeric($data))
            {
                $data = new \DateTime('@' . $data);
            }
            else
            {
                $data = new \DateTime($data);
            }
        }

        return sprintf("'%s'", $data->format('Y-m-d H:i:s.u'));
    }

}

?>
