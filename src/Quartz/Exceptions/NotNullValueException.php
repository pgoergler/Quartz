<?php

namespace Quartz\Exceptions;

class NotNullValueException extends \Exception
{

    public function __construct($msg)
    {
        parent :: __construct($msg);
    }

}
