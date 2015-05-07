<?php

namespace Quartz\Converter\MySQL;

/**
 * Description of StringConverter
 *
 * @author paul
 */
class StringConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return null;
        }
        /* $data = str_replace("'", "''", $data);
          $type = is_null($type) ? '' : sprintf("%s ", $type);
          $data = sprintf("%s'%s'",  $type, $data); */
        $value = preg_replace('#([\'"])(.*?)([\'"])$#i', '$2', $data);
        if (is_numeric($typeParameter) && $typeParameter > 0)
        {
            return substr($value, 0, $typeParameter);
        }
        return $value;
    }

    public function toDb($data, $type, $typeParameter)
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
        if (is_numeric($typeParameter) && $typeParameter > 0)
        {
            $data = substr($data, 0, $typeParameter);
        }
        return sprintf("'%s'", $data);
    }

    public function translate($type, $parameter)
    {
        if ($type == 'text ')
        {
            return 'TEXT';
        }

        $size = '';
        if (preg_match('#\((.*?)\)#', $parameter, $matchs))
        {
            $size = '(' . $matchs[1] . ')';
        }
        return "VARCHAR$size";
    }

}
