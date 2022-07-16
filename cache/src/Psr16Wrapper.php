<?php

namespace Yeast\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;


class Psr16Wrapper implements CacheInterface {
    public function __construct(private CacheItemPoolInterface $pool) {
    }

    public function get($key, $default = null) {
        $item = $this->pool->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    public function set($key, $value, $ttl = null) {
        $item = $this->pool->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        return $this->pool->save($item);
    }

    public function delete($key) {
        return $this->pool->deleteItem($key);
    }

    public function clear() {
        $this->pool->clear();
    }

    public function getMultiple($keys, $default = null) {
        foreach ($this->pool->getItems(is_array($keys) ? $keys : iterator_to_array($keys)) as $key => $item) {
            yield $key => ($item->isHit() ? $item->get() : $default);
        }
    }

    public function setMultiple($values, $ttl = null) {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys) {
        return $this->pool->deleteItems(is_array($keys) ? $keys : iterator_to_array($keys));
    }

    public function has($key) {
        return $this->pool->hasItem($key);
    }
}