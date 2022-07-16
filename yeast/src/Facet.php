<?php

namespace Yeast;


/**
 * A facet is a "facet" of an application, an entry point or responsibility, these e.g. include http, console or message queue
 *
 * @template T of Runtime
 */
interface Facet {

    /**
     * The internal name of this facet
     *
     * @return string
     */
    public function name(): string;

    /**
     * A single runtime for a facet, for e.g. http every request has its own runtime, the same might be done for e.g. a message queue
     *
     * @return T
     */
    public function runtime(): Runtime;
}