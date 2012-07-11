<?php

namespace Quartz\Exceptions;

class NotExistsException extends \Exception
{
    public function __construct($elt, $code = 0, $previous = null)
    {
        parent::__construct("$elt doesn't exists.", $code, $previous);
    }
}