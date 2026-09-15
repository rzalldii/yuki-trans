<?php

declare(strict_types=1);

namespace App\Exceptions\Finance;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient wallet balance.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): never
    {
        throw ValidationException::withMessages([
            'amount' => [$this->getMessage()],
        ]);
    }
}