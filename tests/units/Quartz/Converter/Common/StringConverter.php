<?php

namespace Quartz\Converter\Common\tests\units;

define('__VENDOR_DIR__', preg_replace('#(.*?/vendor)(.*?)$#', '$1', __DIR__));

require_once __VENDOR_DIR__ . '/quartz/Quartz/tests/bootstrap/bootstrap.php';

use \mageekguy\atoum;

/**
 * Description of StringConverter
 *
 * @author paul
 */
class StringConverter extends atoum\test
{

    public function testImplementInterface()
    {
        $this->assert->class('\Quartz\Converter\Common\StringConverter')->hasInterface('\Quartz\Converter\ConverterInterface');
    }

    public function testFromDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\StringConverter())
                ->then
                ->string($converter->fromDb('true', 'string'))->isEqualTo('true')
                ->string($converter->fromDb('false', 'string'))->isEqualTo('false')
                ->string($converter->fromDb(1, 'string'))->isEqualTo('1')
                ->string($converter->fromDb(-123, 'string'))->isEqualTo('-123')
                ->variable($converter->fromDb(null, 'string'))->isEqualTo(null)
                ->string($converter->fromDb('null', 'string'))->isEqualTo('null')
                ->variable($converter->fromDb('NULL', 'string'))->isEqualTo('NULL')
                ->string($converter->fromDb(0, 'string'))->isEqualTo('0')
                ->string($converter->fromDb(1.2, 'string'))->isEqualTo('1.2')
                ->string($converter->fromDb(1.2, 'string'))->isEqualTo('1.2')
                ->string($converter->fromDb('quote \' "-', 'string'))->isEqualTo('quote \' "-')
        ;
    }

    public function testToDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\StringConverter())
                ->then
                ->string($converter->toDb(true, 'string'))->isEqualTo("'true'")
                ->string($converter->toDb(1, 'string'))->isEqualTo("'1'")
                ->string($converter->toDb(123, 'string'))->isEqualTo("'123'")
                ->string($converter->toDb(123.123, 'string'))->isEqualTo("'123.123'")
                ->string($converter->toDb(null, 'string'))->isEqualTo('NULL')
                ->string($converter->toDb(false, 'string'))->isEqualTo("'false'")
                ->string($converter->toDb(0, 'string'))->isEqualTo("'0'")
                ->string($converter->toDb(-1, 'string'))->isEqualTo("'-1'")
                ->string($converter->toDb('quote \' "', 'string'))->isEqualTo("'quote '' \"'")
        ;
    }

}

?>
