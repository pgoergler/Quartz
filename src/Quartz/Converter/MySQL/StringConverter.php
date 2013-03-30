<?php

namespace Quartz\Converter\MySQL;

/**
 * Description of StringConverter
 *
 * @author paul
 */
class StringConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
    {
        if (is_null($data))
        {
            return null;
        }
        /* $data = str_replace("'", "''", $data);
          $type = is_null($type) ? '' : sprintf("%s ", $type);
          $data = sprintf("%s'%s'",  $type, $data); */
        return preg_replace('#([\'"])(.*?)([\'"])$#i', '$2', $data);
    }

    public function toDb($data, $type = null)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        if (is_bool($data))
        {
            return ($data === true) ? "'true'" : "'false'";
        }
        //return "'" . str_replace('\\"', '"', $data) . "'";

        $data = str_replace("'", "''", $data);
        $data = str_replace('\\', '\\\\', $data);
        return sprintf("'%s'", $data);
    }

    public function translate($type)
    {
        if( $type == 'text ')
        {
            return 'TEXT';
        }
        
        $size = '';
        if (preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
        {
            $size = '(' . $matchs[2] . ')';
        }
        return "VARCHAR$size";
    }

}

?>
