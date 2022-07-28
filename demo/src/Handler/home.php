<?php

namespace Yeast\Demo\Handler;

use Yeast\Demo\Entity\Foo;
use Yeast\Http\Attribute\Request\Query;
use Yeast\Http\Attribute\Route;

use function Yeast\Doctrine\HomeCooking\repo;
use function Yeast\Http\HomeCooking\json;
use function Yeast\Twig\HomeCooking\display;


#[Route]
function home(#[Query] string $user) {
    repo(Foo::class);

    echo "Now let the template speak:<p>";
    display("gamers.html.twig", ['user' => $user]);
}

#[Route]
function gibjson() {
    json("gamer");
}