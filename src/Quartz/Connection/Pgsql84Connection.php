<?php

namespace Quartz\Connection;

class Pgsql84Connection extends PgsqlConnection
{
    public function configure()
    {
        $this->registerConverter('Array', new \Quartz\Converter\PgSQL84\ArrayConverter($this), array());
        $this->registerConverter('Json', new \Quartz\Converter\PgSQL84\JsonConverter(), array('json'));
        $this->registerConverter('Boolean', new \Quartz\Converter\PgSQL84\BooleanConverter(), array('bool', 'boolean'));
        $this->registerConverter('Number', new \Quartz\Converter\PgSQL84\NumberConverter(), array('int2', 'int4', 'int8', 'numeric', 'float4', 'float8', 'integer', 'sequence'));
        $this->registerConverter('String', new \Quartz\Converter\PgSQL84\StringConverter(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'string', 'enum'));
        $this->registerConverter('Timestamp', new \Quartz\Converter\PgSQL84\TimestampConverter(), array('timestamp', 'date', 'time', 'datetime', 'unixtime'));
        $this->registerConverter('HStore', new \Quartz\Converter\PgSQL84\HStoreConverter(), array('hstore'));
        $this->registerConverter('Interval', new \Quartz\Converter\PgSQL84\IntervalConverter(), array('interval'));
        //$this->registerConverter('Binary', new Converter\PgBytea(), array('bytea'));
    }
}