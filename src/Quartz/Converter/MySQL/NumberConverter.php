<?php

namespace Quartz\Converter\MySQL;

/**
 * Description of NumberConverter
 *
 * @author paul
 */
class NumberConverter implements \Quartz\Converter\ConverterInterface
{

    public function fromDb($data, $type = null)
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

    public function toDb($data, $type = null)
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

    public function translate($type)
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
                if (!preg_match('#^([a-z0-9_\.-]+)\((.*?)\)$#i', $type, $matchs))
                {
                    return 'INTEGER';
                }
                return 'DECIMAL(' . $matchs[2] . ')';

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

?>
