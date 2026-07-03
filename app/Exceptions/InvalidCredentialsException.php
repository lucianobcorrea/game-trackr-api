<?php

namespace App\Exceptions;

use Exception;

class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid credentials', 401);
    }

    public function render()
    {
        return response()->json(['error' => $this->getMessage()], 401);
    }
}
