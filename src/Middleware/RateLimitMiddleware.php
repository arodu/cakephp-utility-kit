<?php

declare(strict_types=1);

namespace UtilityKit\Middleware;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UtilityKit\Http\Exception\TooManyRequestsException;

/**
 * RateLimit middleware
 * 
 * This middleware limits the number of requests a client can make in a given time period.
 * It uses the client's IP address to track the number of requests.
 * If the limit is exceeded, it throws a TooManyRequestsException.
 * 
 * Usage:
 * In your application, you can add this middleware to your middleware stack:
 * * ```php
 * * $middlewareQueue->add(new \UtilityKit\Middleware\RateLimitMiddleware([
 * * *     'limit' => 1000, // Maximum requests allowed
 * * *     'period' => 3600, // Time period in seconds (default is 1 hour)
 * * * ]));
 * 
 * * Configuration:
 * * You can also configure the default limit and period in your application configuration:
 * * * ```php
 * * * Configure::write('RateLimit', [
 * * * *     'limit' => 1000, // Default maximum requests allowed
 * * * *     'period' => 3600, // Default time period in seconds (default is 1 hour)
 * * * *     'cache' => 'default', // Cache configuration to use
 * * * ]);
 */
class RateLimitMiddleware implements MiddlewareInterface
{

    /**
     * Max limit of requests per period.
     *
     * @var integer
     */
    protected int $limit;

    /**
     * Time period in seconds for the rate limit.
     *
     * @var integer
     */
    protected int $period;

    /**
     * Cache configuration to use.
     *
     * @var string
     */
    protected string $cache;

    /**
     *  Constructor.
     * 
     * @param array $options Options for the rate limit.
     * * Options can include:
     * * - `limit`: Maximum number of requests allowed (default is 1000).
     * * - `period`: Time period in seconds for the rate limit (default is 3600 seconds, or 1 hour).
     * * - `cache`: Cache configuration to use (default is 'default').
     */
    public function __construct(array $options = [])
    {
        $config = Configure::read('RateLimit');
        $this->limit = $options['limit'] ?? $config['limit'] ?? 1000;
        $this->period = $options['period'] ?? $config['period'] ?? 3600; // Default to 1 hour
        $this->cache = $options['cache'] ?? $config['cache'] ?? 'default';
    }

    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Cake\Http\ServerRequest $request */
        $clientIp = $request->clientIp();
        $cacheKey = "rate_limit_{$clientIp}";

        $rateData = Cache::read($cacheKey, $this->cache);

        if (empty($rateData)) {
            $rateData = [
                'count' => 1,
                'timestamp' => time(),
            ];
        } else {
            if ((time() - $rateData['timestamp']) > $this->period) {
                $rateData['count'] = 1;
                $rateData['timestamp'] = time();
            } else {
                $rateData['count']++;
            }
        }

        Cache::write($cacheKey, $rateData, $this->cache);

        if ($rateData['count'] > $this->limit) {
            throw new TooManyRequestsException();
        }

        return $handler->handle($request);
    }
}
