<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of NumberConverter
 *
 * @author paul
 */
class NumberConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === 'NULL' || $data === 'null' || is_null($data))
        {
            return null;
        }

        if (is_numeric($data))
        {
            $data += 0; // force type juggling
            if (is_double($data))
            {
                return doubleval($data);
            } elseif (is_float($data))
            {
                return floatval($data);
            } elseif (is_int($data))
            {
                return intval($data);
            }
        } elseif (is_bool($data))
        {
            return intval($data);
        }
        throw new \InvalidArgumentException($data . ' is not a number (int, float or double)');
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        if (is_numeric($data))
        {
            $data = str_replace(',', '.', $data) + 0; // force type juggling
            if (is_double($data))
            {
                return doubleval($data);
            } elseif (is_float($data))
            {
                return floatval($data);
            } elseif (is_int($data))
            {
                return intval($data);
            }
        } elseif (is_bool($data))
        {
            return intval($data);
        }
        throw new \InvalidArgumentException($data . ' is not a number (int, float or double)');
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }

        switch ($type)
        {
            case 'smallint':
            case 'smallinteger':
            case 'int2':
                return "smallint$array";

            case 'bigint':
            case 'biginteger':
            case 'int8':
                return "bigint$array";

            case 'real':
            case 'float4':
                return "real$array";

            case 'double':
            case 'float8':
                return "double precision$array";

            case 'sequence':
            case 'serial':
                return "serial";
            case 'sequence8':
            case 'bigsequence':
            case 'bigserial':
                return 'bigserial';

            case 'numeric':
            case 'decimal':
                $size = '';
                if (preg_match('#\((.*?)\)#', $parameter, $matchs))
                {
                    $size = '(' . $matchs[1] . ')';
                    return "numeric$size$array";
                }
                return "integer$array";
            default:
                return "integer$array";
        }
    }

}
