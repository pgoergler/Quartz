<?php

namespace Quartz\Converter\Common\tests\units;

define('__VENDOR_DIR__', preg_replace('#(.*?/vendor)(.*?)$#', '$1', __DIR__));

require_once __VENDOR_DIR__ . '/quartz/Quartz/tests/bootstrap/bootstrap.php';

use \mageekguy\atoum;

/**
 * Description of JsonConverter
 *
 * @author paul
 */
class JsonConverter extends atoum\test
{

    public function testImplementInterface()
    {
        $this->assert->class('\Quartz\Converter\Common\JsonConverter')->hasInterface('\Quartz\Converter\ConverterInterface');
    }

    public function testFromDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\JsonConverter())
                ->then
                ->variable($converter->fromDb(null, 'json'))->isEqualTo(null)
                ->variable($converter->fromDb('null', 'json'))->isEqualTo(null)
                ->variable($converter->fromDb('NULL', 'json'))->isEqualTo(null)
                ->array($converter->fromDb('{NULL}', 'json'))->isEqualTo(array())
                ->array($converter->fromDb('{}', 'json'))->isEqualTo(array())
                ->array($converter->fromDb('[]', 'json'))->isEqualTo(array())
                
                
                ->boolean($converter->fromDb('true', 'json'))->isEqualTo(true)
                ->boolean($converter->fromDb('false', 'json'))->isEqualTo(false)
                
                ->float($converter->fromDb('1.23', 'json'))->isEqualTo(1.23)
                ->integer($converter->fromDb(1, 'json'))->isEqualTo(1)
                ->integer($converter->fromDb(-123, 'json'))->isEqualTo(-123)
                
                ->string($converter->fromDb('"-123.123"', 'json'))->isEqualTo('-123.123')
                
                ->variable($converter->fromDb('null', 'json'))->isEqualTo(null)
                ->variable($converter->fromDb('NULL', 'json'))->isEqualTo(null)
                ->variable($converter->fromDb(0, 'json'))->isEqualTo(null)
                
                
                ->string($converter->fromDb('"foo"', 'json'))->isEqualTo('foo')
        ;
    }

    public function testToDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\JsonConverter())
                ->then
                ->string($converter->toDb(null, 'json'))->isEqualTo('NULL')
                ->string($converter->toDb(1, 'json'))->isEqualTo('1')
                ->string($converter->toDb(true, 'json'))->isEqualTo('true')
                ->string($converter->toDb(false, 'json'))->isEqualTo('false')
                ->string($converter->toDb(123.123, 'json'))->isEqualTo('123.123')
                ->string($converter->toDb('-123.123', 'json'))->isEqualTo('"-123.123"')
                ->string($converter->toDb('foo', 'json'))->isEqualTo('"foo"')
                ->string($converter->toDb(array(1,2,'3'), 'json'))->isEqualTo('[1,2,"3"]')
                ->string($converter->toDb(array('foo' => 'bar',2,'3'), 'json'))->isEqualTo('{"foo":"bar","0":2,"1":"3"}')
                ->string($converter->toDb(array('foo' => '"b\'ar"',2,'3'), 'json'))->isEqualTo('{"foo":"\\"b\'ar\\"","0":2,"1":"3"}')
                ;
    }

}

?>
