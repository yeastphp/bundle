<?php

use Yeast\Demo\App;
use Yeast\Http\Facet\Http;
use Yeast\Kernel;


include __DIR__ . "/../vendor/autoload.php";

$httpRuntime = Kernel::run(App::class, Http::class, __DIR__ . '/..');
return $httpRuntime->handle();