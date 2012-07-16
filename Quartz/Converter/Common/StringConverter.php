<?php

namespace Quartz\Converter\Common;

/**
 * Description of StringConverter
 *
 * @author paul
 */
class StringConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
    {
        if( is_null($data) )
        {
            return null;
        }
        /*$data = str_replace("'", "''", $data);
        $type = is_null($type) ? '' : sprintf("%s ", $type);
        $data = sprintf("%s'%s'",  $type, $data);*/
        return preg_replace('#([\'"])(.*?)([\'"])$#i', '$2', $data);
    }

    public function toDb($data, $type = null)
    {
        if( is_null($data) )
        {
            return 'NULL';
        }
        //return "'" . str_replace('\\"', '"', $data) . "'";
        $data = str_replace("'", "''", $data);
        $data = str_replace('\\', '\\\\', $data);
        return sprintf("'%s'",  $data);
    }

}

?>
