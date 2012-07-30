<?php

namespace Quartz\Converter\Common\tests\units;

define('__VENDOR_DIR__', preg_replace('#(.*?/vendor)(.*?)$#', '$1', __DIR__));

require_once __VENDOR_DIR__ . '/quartz/Quartz/tests/bootstrap/bootstrap.php';

use \mageekguy\atoum;

/**
 * Description of BooleanConverter
 *
 * @author paul
 */
class BooleanConverter extends atoum\test
{

    public function testImplementInterface()
    {
        $this->assert->class('\Quartz\Converter\Common\BooleanConverter')->hasInterface('\Quartz\Converter\ConverterInterface');
    }

    public function testFromDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\BooleanConverter())
                ->then
                ->boolean($converter->fromDb(true, 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb(1, 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb(123, 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('t', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('T', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('true', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('TRUE', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('on', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('ON', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('yes', 'boolean'))->isEqualTo(true)
                ->boolean($converter->fromDb('YES', 'boolean'))->isEqualTo(true)
                
                ->variable($converter->fromDb(null, 'boolean'))->isEqualTo(null)
                ->variable($converter->fromDb('null', 'boolean'))->isEqualTo(null)
                ->variable($converter->fromDb('NULL', 'boolean'))->isEqualTo(null)
                
                ->boolean($converter->fromDb(false, 'boolean'))->isEqualTo(false)
                ->boolean($converter->fromDb('false', 'boolean'))->isEqualTo(false)
                ->boolean($converter->fromDb(0, 'boolean'))->isEqualTo(false)
                ;
    }
    
    public function testToDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\BooleanConverter())
                ->then
                
                ->string($converter->toDb(true, 'boolean'))->isEqualTo('true')
                ->string($converter->toDb(1, 'boolean'))->isEqualTo('true')
                ->string($converter->toDb(123, 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('t', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('T', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('true', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('TRUE', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('on', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('ON', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('yes', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb('YES', 'boolean'))->isEqualTo('true')
                ->string($converter->toDb(null, 'boolean'))->isEqualTo('NULL')
                ->string($converter->toDb(false, 'boolean'))->isEqualTo('false')
                ->string($converter->toDb(0, 'boolean'))->isEqualTo('false')
                    ;
    }

}

?>
