<?php

namespace Quartz\Converter;

/**
 * Description of ConverterInterface
 *
 * @author paul
 */
interface ConverterInterface
{
    /**
     * fromDb
     *
     * Parse the output string from the database and returns the converted value 
     * into an according PHP representation.
     *
     * @param $data String  Input string from Db row result.
     * @param $type String  Optional type.
     * @return Mixed PHP respresentation of the data.
     **/
    public function fromDb($data, $type = null);

    /**
     * toDb
     *
     * Convert a PHP representation into the according Db formatted string.
     *
     * @param $data Mixed   PHP representation.
     * @param $type String  Optional type.
     * @return String Db converted string for input.
     **/
    public function toDb($data, $type = null);
}

?>
