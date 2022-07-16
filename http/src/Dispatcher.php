<?php

namespace Yeast\Http;

use DI\Container;
use DI\Definition\Resolver\ResolverDispatcher;
use DI\Invoker\DefinitionParameterResolver;
use DI\Proxy\ProxyFactory;
use FileEye\MimeMap\Extension;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yeast\HomeCooking;
use Yeast\Http\Internal\Action\ControllerAction;
use Yeast\Http\Internal\Action\FunctionAction;
use Yeast\Http\Internal\RouteCollector;
use Yeast\Http\Internal\Router;
use Yeast\Http\Internal\Router\RouteContext;
use Yeast\Kernel;


final class Dispatcher {
    private Router $router;
    private Invoker $invoker;
    private Module $module;

    public function __construct(private Kernel $kernel, private LoggerInterface $logger, private Container $container) {
        $this->logger->debug('Created dispatcher, building router');
        $this->buildRouter();
        $this->logger->debug('building invoker');
        $this->buildInvoker();
        $this->module = $this->kernel->module(Module::class);
    }

    public function buildRouter() {
        $routerCollector = new RouteCollector($this->kernel, $this->logger, $this->container, $this->kernel->module(Module::class));
        $this->router    = $routerCollector->build();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        [$result, $data] = $this->router->resolve($request->getMethod(), $request->getUri()->getPath());

        if ($result === \FastRoute\Dispatcher::FOUND) {
            return $this->handleRoute($request, $data);
        }

        if ($result === \FastRoute\Dispatcher::NOT_FOUND) {
            return $this->handleNotFound($request);
        }

        return $this->handleMethodNotAllowed($data);
    }

    private function handleRoute(ServerRequestInterface $request, RouteContext $context): ResponseInterface {
        $action   = $context->action;
        $callable = null;
        if ($action instanceof ControllerAction) {
            $controller = $this->container->get($context->action->controller);
            $callable   = [$controller, $context->action->method];
        }

        if ($action instanceof FunctionAction) {
            $callable = $action->function;
        }

        if ($callable === null) {
            throw new RuntimeException("No support for action type of " . get_class($action));
        }

        $parameters = [];

        foreach ($action->parameters as $key => $parameter) {
            $parameters[$key] = $parameter->resolve($request);
        }

        $homeCooking = HomeCooking::isEnabled();
        if ($homeCooking) {
            ob_start();
        }

        $resp = $this->invoker->call($callable, [
          ...$context->params,
          ...$parameters,
          RequestInterface::class       => $request,
          ServerRequestInterface::class => $request,
        ]);

        if ($homeCooking) {
            $data = ob_get_clean();

            if ($resp === null) {
                $this->logger->debug('No return value given, retrieving data from output buffer');

                $headerList = headers_list();
                header_remove();
                $version    = '1.1';
                $statusCode = 200;
                $reason     = null;
                $headers    = [];

                foreach ($headerList as $header) {
                    $pos = strpos($header, ':');
                    if ($pos === false && str_starts_with(strtolower(substr($header, 0, 4)), 'http') && preg_match(':^http\/(\d+(?:\.\d+)?) (\d)(?:\s+(.+))?:i', $header, $match) === 1) {
                        $version    = $match[1];
                        $statusCode = intval($match[2]);
                        $reason     = $match[3];
                        continue;
                    }

                    if ($pos === false) {
                        throw new RuntimeException("Invalid header line given `" . $header . "`, missing header value");
                    }

                    $name             = strtolower(substr($header, 0, $pos));
                    $headers[$name]   ??= [];
                    $headers[$name][] = substr($header, $pos + 2);
                }

                if ( ! isset($headers['content-type'])) {
                    $headers['content-type'] = ['text/html; charset=UTF-8'];
                }

                return new Response(
                  status:  $statusCode,
                  headers: $headers,
                  body:    Utils::streamFor($data),
                  version: $version,
                  reason:  $reason
                );
            }
        } else {
            if ($resp === null) {
                // TODO: error, no response
            }
        }

        return $resp;
    }

    private function handleNotFound(ServerRequestInterface $request): ResponseInterface {
        $path = $request->getUri()->getPath();
        foreach ($this->module->getMounts() as $mount) {
            if ($mount->type !== MountType::FILES || $mount->directories === null) {
                continue;
            }

            if ( ! str_starts_with($path, $mount->prefix)) {
                continue;
            }

            $filePath = substr($path, strlen($mount->prefix));
            foreach ($mount->directories as $dir) {
                $fullPath = $dir . '/' . $filePath;
                if (is_dir($fullPath)) {
                    $fullPath .= '/index.html';
                }

                if ( ! file_exists($fullPath)) {
                    continue;
                }

                return $this->handleFileDownload($request, $fullPath);
            }
        }

        return new Response(404);
    }

    private function handleFileDownload(ServerRequestInterface $request, string $path): ResponseInterface {
        $lastPoint      = strrpos($path, ".");
        $extension      = substr($path, $lastPoint + 1);
        $extensionClass = new Extension($extension);
        $contentType    = $extensionClass->getDefaultType(false);

        $fopen = Utils::tryFopen($path, 'r+');

        return new Response(200, ["content-type" => [$contentType]], Utils::streamFor($fopen));
    }

    private function handleMethodNotAllowed(array $data): ResponseInterface {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return (new Response(405))->withHeader('Allow', implode(", ", $data));
    }

    private function buildInvoker() {
        $parameterResolver = new ResolverChain(
          [
            new AssociativeArrayResolver,
            new DefinitionParameterResolver(new ResolverDispatcher($this->container, new ProxyFactory($this->kernel->getCacheDir() . '/container/proxies'))),
            new DefaultValueResolver,
            new TypeHintResolver(),
            new TypeHintContainerResolver($this->container),
          ]
        );

        $this->invoker = new Invoker($parameterResolver, $this->container);
    }
}