<?php

namespace Yeast\Http\Phppm;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPPM\Bridges\BridgeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yeast\Http\Facet\Http;
use Yeast\Kernel;


class Bridge implements BridgeInterface {
    private Http $facet;

    public function bootstrap($appBootstrap, $appenv, $debug) {
        $kernel      = Kernel::create($appBootstrap, getcwd());
        $this->facet = $kernel->facet(Http::class);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $runtime = $this->facet->runtime();

        try {
            $response = $runtime->handleRequest($request);
        } catch (Throwable $e) {
            $response = (new Response(500))->withBody(Utils::streamFor("oh no :(\n\n" . $e));
        }

        return $response;
    }
}