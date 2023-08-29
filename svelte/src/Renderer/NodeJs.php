<?php

namespace Yeast\Svelte\Renderer;

use RuntimeException;
use Yeast\Svelte\Renderer;
use Yeast\Svelte\RenderResult;


class NodeJs implements Renderer
{
    private $process = null;
    private $pipes;
    private $buffer = "";

    public function __construct(private readonly string $nodeModules)
    {
    }

    public function isAvailable(): bool
    {
        foreach (explode(PATH_SEPARATOR, $_ENV['PATH'] ?: "") as $prefix) {
            if (file_exists($prefix . '/node')) {
                return true;
            }
        }

        return false;
    }

    private function startNodeJs()
    {
        if ($this->process !== null) {
            return;
        }

        $this->process = proc_open(["node", __DIR__ . "/../../resources/nodejs_render.js"], [["pipe", "r"], ["pipe", "w"]], $this->pipes, cwd: __DIR__ . '/../..', env_vars: ['NODE_PATH' => $this->nodeModules]);

        if ( ! $this->process) {
            throw new RuntimeException("Failed to start NodeJS renderer");
        }
    }

    public function render($component, array $props = [], array $context = []): RenderResult
    {
        $obj = json_encode([
          "component" => $component,
          "props"     => $props,
          "context"   => $context,
        ]);

        $this->startNodeJs();

        fwrite($this->pipes[0], $obj . "\n");

        while (strrpos($this->buffer, "\n") === false) {
            $this->buffer .= fread($this->pipes[1], 4096);
        }

        $pos          = strpos($this->buffer, "\n");
        $res          = substr($this->buffer, 0, $pos);
        $this->buffer = substr($this->buffer, $pos + 1);

        $obj = json_decode($res, true);

        if ($obj['status'] === 'success') {
            return new RenderResult($obj['response']['head'], $obj['response']['html'], $obj['response']['css']);
        }

        throw new RuntimeException("render failed with message: " . $obj['error']);
    }

    public function __destruct()
    {
        if ($this->process !== null && $this->process !== false) {
            proc_close($this->process);
        }
    }
}