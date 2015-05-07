<?php

namespace Quartz\Exception;

class NotNullValueException extends SqlException
{

    public function __construct($msg)
    {
        parent :: __construct($msg);
    }

}
