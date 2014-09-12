<?php

namespace Quartz\Exception;

class NotNullValueException extends \Exception
{

    public function __construct($msg)
    {
        parent :: __construct($msg);
    }

}
