<?php

namespace Yeast\Http\Runtime;

use DI\Container;
use GuzzleHttp\Psr7\HttpFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Yeast\Http\Dispatcher;
use Yeast\Kernel;
use Yeast\Runtime;


class HttpRuntime implements Runtime
{
    private ServerRequestCreator $creator;
    private EmitterInterface $emitter;

    public function __construct(private Kernel $kernel, private LoggerInterface $logger, private Dispatcher $dispatcher, private HttpFactory $factory)
    {
        $this->creator = new ServerRequestCreator($this->factory, $this->factory, $this->factory, $this->factory);
        $this->emitter = new SapiStreamEmitter();
    }

    public function handle(): ?bool
    {
        $request = $this->creator->fromGlobals();

        if (php_sapi_name() === 'cli-server') {
            $filePath = $this->kernel->getApplicationDir() . '/public' . $request->getUri()->getPath();
            if (file_exists($filePath) && ! (is_dir($filePath) || str_ends_with($filePath, '.php'))) {
                $this->logger->debug('CLI server used and file exists on ' . $filePath . ' delegating file transfer to PHP');

                return false;
            };
        }

        $response = $this->handleRequest($request);

        if ( ! $this->emitter->emit($response)) {
            return false;
        }

        return null;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request
          ->withAttribute(ContainerInterface::class, $this->kernel->getContainer())
          ->withAttribute(Container::class, $this->kernel->getContainer())
          ->withAttribute(Kernel::class, $this->kernel);

        return $this->dispatcher->handle($request);
    }
}