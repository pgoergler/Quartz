<?php

namespace Quartz\Converter\PgSQL84;

/**
 * Description of HStoreConverter
 *
 * @author paul
 */
class HStoreConverter implements \Quartz\Converter\ConverterInterface
{
    
    public function fromDb($data, $type = null)
    {
        if( $data == 'NULL' )
        {
            return null;
        }
        
        if( $data == "''::hstore" )
        {
            return array();
        }
        
        $data = preg_replace("#'(.*?)'::hstore#", '$1', $data);
        $split = preg_split('/[,\s]*"([^"]+)"[,\s]*|[,=>\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $hstore = array();
        
        for ($index = 0; $index < count($split); $index = $index + 2)
        {
            $hstore[$split[$index]] = $split[$index + 1] != 'NULL' ? $split[$index + 1] : null;
        }

        return $hstore;
    }

    public function toDb($data, $type = null)
    {
        if( is_null($data) )
        {
            return "''::hstore";
        }
        
        if (!is_array($data))
        {
            throw new \Exception(sprintf("HStore::toDb takes an associative array as parameter ('%s' given).", gettype($data)));
        }

        $insert_values = array();

        foreach($data as $key => $value)
        {
            if (is_null($value))
            {
                $insert_values[] = sprintf('"%s" => NULL', $key);
            }
            elseif( is_array($value) )
            {
                $insert_values[] = sprintf('"%s" => "%s"', $key, str_replace('"', '\\\\"', json_encode($value)));
            }
            else
            {
                $insert_values[] = sprintf('"%s" => "%s"', $key, $value);
            }
        }

        return sprintf("'%s'::hstore", join(', ', $insert_values));
    }
}

?>
