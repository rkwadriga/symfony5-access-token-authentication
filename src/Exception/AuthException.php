<?php declare(strict_types=1);

namespace App\Exception;

class AuthException extends \Exception
{
    public string $name = 'Authentication failed';
}