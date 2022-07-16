<?php

namespace Yeast\Demo\Controller;

use DI\Attribute\Inject;
use Yeast\Http\Attribute\Controller;
use Yeast\Http\Attribute\Route;
use Yeast\Kernel;


#[Controller]
class IndexController {
    #[Inject]
    public Kernel $kernel;

    #[Route("/")]
    public function index() {
    }
}