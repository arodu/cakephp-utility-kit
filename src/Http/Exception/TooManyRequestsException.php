<?php
declare(strict_types=1);

namespace UtilityKit\Http\Exception;

use Cake\Http\Exception\HttpException;
use Throwable;

/**
 * HTTP 429 error.
 */
class TooManyRequestsException extends HttpException
{
    /**
     * @inheritDoc
     */
    protected $_defaultCode = 429;

    /**
     * Constructor
     *
     * @param string|null $message If no message is given 'TooManyRequests' will be the message
     * @param int|null $code Status code, defaults to 429
     * @param \Throwable|null $previous The previous exception.
     */
    public function __construct(?string $message = null, ?int $code = null, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = 'Too Many Requests';
        }
        parent::__construct($message, $code, $previous);
    }
}
