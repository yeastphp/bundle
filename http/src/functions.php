<?php

namespace Yeast\Http;

function mount(string $name, MountType $type, string|array $namespaces, string $prefix = "/", bool $debugOnly = false, string|array|null $directories = null): Mount {
    return new Mount($name, $type, (array)$namespaces, $directories === null ? null : (array)$directories, $prefix, $debugOnly);
}

function handler(string $name, string|array $namespaces, string $prefix = "/", bool $debugOnly = false, string|array|null $directories = null): Mount {
    return mount($name, MountType::HANDLER, $namespaces, $prefix, $debugOnly, $directories);
}

function controller(string $name, string|array $namespaces, string $prefix = "/", bool $debugOnly = false, string|array|null $directories = null): Mount {
    return mount($name, MountType::CONTROLLER, $namespaces, $prefix, $debugOnly, $directories);
}

function files(string $name, string|array $directories, string $prefix = "/", bool $debugOnly = false): Mount {
    return mount($name, MountType::FILES, [], $prefix, $debugOnly, $directories);
}