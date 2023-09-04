<?php

namespace Yeast\Graphql\Controller;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\GraphQLite\Http\WebonyxGraphqlMiddleware;
use Yeast\Http\Attribute\Controller;
use Yeast\Http\Attribute\Route;


#[Controller]
class GraphqlController implements RequestHandlerInterface
{
    public function __construct(
        private WebonyxGraphqlMiddleware $graphqlMiddleware,
        private Schema                   $schema,
        private LoggerInterface          $logger,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(400);
    }

    #[Route('/graphql', method: 'OPTIONS')]
    public function cors(ServerRequestInterface $request): ResponseInterface
    {
        return $this->withCors($request, new Response());
    }

    /**
     * @template T of ResponseInterface
     * @psalm-param T $response
     * @psalm-return  T
     */
    private function withCors(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $request->hasHeader('Origin') ? $request->getHeader('Origin') : '*')
            ->withHeader('Access-Control-Allow-Headers', 'content-type')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST')
            ->withHeader('Access-Control-Max-Age', '600');
    }

    #[Route('/graphql', method: ['POST', 'GET'])]
    public function graphql(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->debug('Handling graphql request');

        if (isset($request->getQueryParams()['schema'])) {
            return new Response(200, ['content-type' => ['text/plain']], body: Utils::streamFor(SchemaPrinter::doPrint($this->schema)));
        }

        $resp = $this->graphqlMiddleware->process($request, $this);

        return $this->withCors($request, $resp);
    }
}