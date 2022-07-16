<?php

namespace Yeast\HomeCooking;

use Yeast\Application;
use Yeast\HomeCooking;
use Yeast\Kernel;


function kernel(): Kernel {
    return HomeCooking::getKernel();
}

function app(): Application {
    return HomeCooking::getKernel()->getApplication();
}