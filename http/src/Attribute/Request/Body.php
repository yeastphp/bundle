<?php

namespace Yeast\Http\Attribute\Request;

use Attribute;
use Psr\Http\Message\ServerRequestInterface;
use Yeast\Loafpan\Loafpan;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Body implements ParameterResolver
{
    public function __construct(
        private ?string $unit = null,
    )
    {
    }

    public function setParameterName(string $name): void
    {
        // TODO: Implement setParameterName() method.
    }

    public function resolve(ServerRequestInterface $request): mixed
    {

        $body = $request->getParsedBody();

        /** @var Loafpan $loafpan */
        $loafpan = $request->getAttribute(Loafpan::class);

        return $loafpan->expand($this->unit, $body);
    }

    public function setParameterType(string $type): void
    {
        if ($this->unit === null) {
            $this->unit = $type;
        }
    }
}