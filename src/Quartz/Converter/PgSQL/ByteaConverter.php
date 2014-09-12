<?php

namespace Quartz\Converter\PgSQL;

/**
 * Description of ByteaConverter
 *
 * @author paul
 */
class ByteaConverter implements \Quartz\Converter\Converter
{

    public function fromDb($data, $type = null)
    {
        if ($data === null || $data === '')
        {
            return null;
        }

        return pg_unescape_bytea($data);
    }

    public function toDb($data, $type = null)
    {
        return sprintf("bytea E'%s'", addcslashes(pg_escape_bytea($data), '\\'));
    }

    public function translate($type)
    {
        return 'bytea';
    }

}
