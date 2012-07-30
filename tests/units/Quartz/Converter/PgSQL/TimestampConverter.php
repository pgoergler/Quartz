<?php

namespace Quartz\Converter\PgSQL\tests\units;

define('__VENDOR_DIR__', preg_replace('#(.*?/vendor)(.*?)$#', '$1', __DIR__));

require_once __VENDOR_DIR__ . '/quartz/Quartz/tests/bootstrap/bootstrap.php';

use \mageekguy\atoum;

/**
 * Description of TimestampConverter
 *
 * @author paul
 */
class TimestampConverter extends atoum\test
{

    public function testImplementInterface()
    {
        $this->assert->class('\Quartz\Converter\PgSQL\TimestampConverter')->hasInterface('\Quartz\Converter\ConverterInterface');
    }

    public function testFromDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\PgSQL\TimestampConverter())
                ->then
                ->variable($converter->fromDb(null, 'string'))->isEqualTo(null)
                
                ->object($converter->fromDb('timestamp \'2012-01-01 23:59:59.000000\'', 'string'))->isInstanceOf('\Datetime')->isEqualTo(new \DateTime('2012-01-01 23:59:59'))
                
        ;
    }

    public function testToDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\PgSQL\TimestampConverter())
                ->then
                ->string($converter->toDb(null, 'string'))->isEqualTo('NULL')
                ->string($converter->toDb(new \DateTime('2012-01-01 23:59:59'), 'string'))->isEqualTo('timestamp \'2012-01-01 23:59:59.000000\'')
        ;
    }

}

?>
