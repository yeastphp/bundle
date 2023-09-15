<?php

namespace Yeast\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyParser implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isJson = str_starts_with(str_replace(' ', '', strtolower(trim($request->getHeaderLine('Content-Type') ?: ''))), 'application/json');

        if ($isJson) {
            $body = json_decode($request->getBody()->getContents(), true);
            $request = $request->withParsedBody($body);
        }

        return $handler->handle($request);
    }
}