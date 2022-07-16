<?php

namespace Yeast\Graphql\Controller;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\GraphQLite\Http\WebonyxGraphqlMiddleware;
use Yeast\Http\Attribute\Controller;
use Yeast\Http\Attribute\Route;


#[Controller]
class GraphqlController implements RequestHandlerInterface {
    public function __construct(private WebonyxGraphqlMiddleware $graphqlMiddleware, private Schema $schema) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        return new Response(400);
    }

    #[Route('/graphql', method: ['POST', 'GET'])]
    public function graphql(ServerRequestInterface $request): ResponseInterface {
        if (isset($request->getQueryParams()['schema'])) {
            return new Response(200, ['content-type' => ['text/plain']], body: Utils::streamFor(SchemaPrinter::doPrint($this->schema)));
        }

        return $this->graphqlMiddleware->process($request, $this);
    }
}