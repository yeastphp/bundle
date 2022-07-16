#!/usr/bin/env php
<?php

use Yeast\Config\YeastConfig;
use Yeast\Loafpan\Loafpan;


include_once __DIR__ . '/../vendor/autoload.php';

$l = new Loafpan(sys_get_temp_dir(), casing: 'kebab-case');

echo json_encode($l->jsonSchema(YeastConfig::class), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";