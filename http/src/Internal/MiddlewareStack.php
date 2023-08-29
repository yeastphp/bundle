<?php

namespace Yeast\Http\Internal;

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;


class MiddlewareStack implements RequestHandlerInterface
{
    public function __construct(private readonly Container $container, private array $middleware, private $handler, private LoggerInterface $logger)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middleware) === 0) {
            $this->logger->debug('Executing handler');
            return ($this->handler)($request);
        }

        $middlewareName = array_shift($this->middleware);
        /** @var MiddlewareInterface $middleware */
        $middleware = $this->container->get($middlewareName);

        $this->logger->debug('Executing middleware: ' . get_class($middleware));
        return $middleware->process($request, $this);
    }
}