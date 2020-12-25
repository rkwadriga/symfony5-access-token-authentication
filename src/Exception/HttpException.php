<?php declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException as BaseException;
use Symfony\Component\HttpFoundation\Response;

class HttpException extends BaseException
{
    public $message = 'Something went wrong!';

    public function __construct(\Throwable $exception, ?string $message = null, ?int $statusCode = null, array $headers = [])
    {
        if ($message === null) {
            $message = $exception->getMessage();
        }
        if ($statusCode === null) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

            if ($exception instanceof ValidationFailedException) {
                $statusCode = Response::HTTP_BAD_REQUEST;
            } elseif ($exception instanceof AuthException) {
                $statusCode = $exception->getCode();
            } elseif ($exception instanceof BaseException) {
                $statusCode = $exception->getStatusCode();
            }
        }

        parent::__construct($statusCode, $message, $exception, $headers, $exception->getCode());
    }
}