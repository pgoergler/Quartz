<?php

namespace Quartz\Exceptions;

/**
 * Description of FieldFormatException
 *
 * @author paul
 */
class FieldFormatException extends \InvalidArgumentException
{

    protected $field;
    protected $value;

    public function __construct($field, $value, $message)
    {
        $this->field = $field;
        $this->value = $value;
        parent::__construct($message);
    }

    public function getField()
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

}

?>
