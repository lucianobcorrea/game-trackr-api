<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid credentials', 401);
    }

    public function render(): JsonResponse
    {
        return response()->json(['error' => $this->getMessage()], 401);
    }
}
