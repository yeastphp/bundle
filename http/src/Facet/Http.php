<?php

namespace Yeast\Http\Facet;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Log\LoggerInterface;
use Yeast\Facet;
use Yeast\Http\Dispatcher;
use Yeast\Http\Module;
use Yeast\Http\Runtime\HttpRuntime;
use Yeast\Kernel;
use Yeast\Runtime;


/**
 * @implements Facet<HttpRuntime>
 */
final class Http implements Facet {


    public function name(): string {
        return "http";
    }

    public function __construct(
      private Kernel $kernel,
      private LoggerInterface $logger,
      private Dispatcher $dispatcher,
      private HttpFactory $factory,
    ) {
        if ( ! $this->kernel->hasModule(Module::class)) {
            throw new \RuntimeException("Can't use HTTP facet without including Yeast\Http module");
        }
    }

    public function runtime(): Runtime {
        return new HttpRuntime($this->kernel, $this->logger, $this->dispatcher, $this->factory);
    }
}