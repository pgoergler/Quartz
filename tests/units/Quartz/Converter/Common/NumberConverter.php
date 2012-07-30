<?php

namespace Quartz\Converter\Common\tests\units;

define('__VENDOR_DIR__', preg_replace('#(.*?/vendor)(.*?)$#', '$1', __DIR__));

require_once __VENDOR_DIR__ . '/quartz/Quartz/tests/bootstrap/bootstrap.php';

use \mageekguy\atoum;

/**
 * Description of NumberConverter
 *
 * @author paul
 */
class NumberConverter extends atoum\test
{

    public function testImplementInterface()
    {
        $this->assert->class('\Quartz\Converter\Common\NumberConverter')->hasInterface('\Quartz\Converter\ConverterInterface');
    }

    public function testFromDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\NumberConverter())
                ->then
                ->integer($converter->fromDb(true, 'integer'))->isEqualTo(1)
                ->integer($converter->fromDb(1, 'integer'))->isEqualTo(1)
                ->integer($converter->fromDb(123, 'integer'))->isEqualTo(123)
                ->integer($converter->fromDb(-123, 'integer'))->isEqualTo(-123)
                ->variable($converter->fromDb(null, 'integer'))->isEqualTo(null)
                ->variable($converter->fromDb('null', 'integer'))->isEqualTo(null)
                ->variable($converter->fromDb('NULL', 'integer'))->isEqualTo(null)
                ->integer($converter->fromDb(false, 'integer'))->isEqualTo(0)
                ->integer($converter->fromDb(0, 'integer'))->isEqualTo(0)
                ->float($converter->fromDb(1.2, 'integer'))->isEqualTo(1.2)
                ->float($converter->fromDb('1.2', 'integer'))->isEqualTo(1.2)
                ->float($converter->fromDb('-1.2', 'integer'))->isEqualTo(-1.2)
        ;

        $tests = array(
            'true', 'false', 't', 'T', '123t', 'a456'
        );

        foreach ($tests as $t)
        {
            $this->exception(function() use($t, $converter)
                            {
                                $converter->fromDb($t, 'integer');
                            })
                    ->isInstanceOf('\InvalidArgumentException')
                    ->hasMessage(sprintf('%s is not a number (int, float or double)', $t));
        }
    }

    public function testToDb()
    {
        $this
                ->if($converter = new \Quartz\Converter\Common\NumberConverter())
                ->then
                ->integer($converter->toDb(true, 'integer'))->isEqualTo(1)
                ->integer($converter->toDb(1, 'integer'))->isEqualTo(1)
                ->integer($converter->toDb(123, 'integer'))->isEqualTo(123)
                ->float($converter->toDb(123.123, 'integer'))->isEqualTo(123.123)
                ->string($converter->toDb(null, 'integer'))->isEqualTo('NULL')
                ->integer($converter->toDb(false, 'integer'))->isEqualTo(0)
                ->integer($converter->toDb(0, 'integer'))->isEqualTo(0)
                ->integer($converter->toDb(-1, 'integer'))->isEqualTo(-1)
                ->integer($converter->toDb('-12', 'integer'))->isEqualTo(-12)
                ->float($converter->toDb('-1.23', 'integer'))->isEqualTo(-1.23)
        ;
        
        $tests = array(
            'true', 'false', 't', 'T', '123t', 'a456', 'null'
        );
        foreach ($tests as $t)
        {
            $this->exception(function() use($t, $converter)
                            {
                                $converter->toDb($t, 'integer');
                            })
                    ->isInstanceOf('\InvalidArgumentException')
                    ->hasMessage(sprintf('%s is not a number (int, float or double)', $t));
        }
    }

}

?>
