<?php

namespace Quartz\Exception;

class NotExistsException extends SqlException
{

    public function __construct($elt = 'undef', $code = 0, $previous = null)
    {
        parent::__construct("$elt doesn't exists.", $code, $previous);
    }

}
