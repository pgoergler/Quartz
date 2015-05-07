<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of IntervalConverter
 *
 * @author paul
 */
class IntervalConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type, $typeParameter)
    {
        if ($data === 'NULL' || $data === 'null' || $data === null || $data === '')
        {
            return null;
        }

        if (preg_match("/^P/", $data))
        {
            return new \DateInterval($data);
        }

        if (preg_match("/(?:([0-9]+) years? ?)?(?:([0-9]+) mons? ?)?(?:([0-9]+) days? ?)?(?:([0-9]{1,2}):([0-9]{1,2}):([0-9]+))?/", $data, $matchs))
        {
            return \DateInterval::createFromDateString(
                            sprintf("%d years %d months %d days %d hours %d minutes %d seconds", array_key_exists(1, $matchs) ? (is_null($matchs[1]) ? 0 : (int) $matchs[1]) : 0, array_key_exists(2, $matchs) ? (is_null($matchs[2]) ? 0 : (int) $matchs[2]) : 0, array_key_exists(3, $matchs) ? (is_null($matchs[3]) ? 0 : (int) $matchs[3]) : 0, array_key_exists(4, $matchs) ? (is_null($matchs[4]) ? 0 : (int) $matchs[4]) : 0, array_key_exists(5, $matchs) ? (is_null($matchs[5]) ? 0 : (int) $matchs[5]) : 0, array_key_exists(6, $matchs) ? (is_null($matchs[6]) ? 0 : (int) $matchs[6]) : 0
            ));
        }

        throw new Exception(sprintf("Data '%s' is not a valid interval.", $data));
    }

    public function toDb($data, $type, $typeParameter)
    {
        if (is_null($data))
        {
            return 'NULL';
        }

        if (!$data instanceof \DateInterval)
        {
            $data = \DateInterval::createFromDateString($data);
        }

        return sprintf("interval '%s'", $data->format('%Y years %M months %D days %H:%i:%S'));
    }

    public function translate($type, $parameter)
    {
        $array = '';
        if (preg_match('#\[(.*)\]#', $parameter, $matchs))
        {
            $array = '[' . $matchs[1] . ']';
        }
        return "interval$array";
    }

}
