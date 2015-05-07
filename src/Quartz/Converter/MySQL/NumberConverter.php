<?php

namespace Quartz\Converter\MySQL;

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

    public function translate($type, $parameter)
    {
        switch ($type)
        {
            case 'smallint':
            case 'int2':
                return 'SMALLINT';

            case 'bigint':
            case 'int8':
                return 'BIGINT';

            case 'numeric':
            case 'decimal':
                $size = '';
                if (preg_match('#\((.*?)\)#', $parameter, $matchs))
                {
                    $size = '(' . $matchs[1] . ')';
                    return "DECIMAL$size";
                }
                return 'INTEGER';

            case 'real':
            case 'float4':
                return 'FLOAT';

            case 'double':
            case 'float8':
                return 'DOUBLE';

            case 'sequence':
                return 'SERIAL';
            case 'sequence8':
                return 'SERIAL';

            default:
                return 'INTEGER';
        }
    }

}
