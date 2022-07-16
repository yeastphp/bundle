<?php
/** @noinspection PhpMultipleClassDeclarationsInspection, PhpIllegalPsrClassPathInspection, PhpIgnoredClassAliasDeclaration */

namespace PHPPM\Bridges;

use Yeast\Http\Phppm\Bridge;


// when calling ppm start --bridge=<name> it'll first try it as full class name and then try PHPPM\Bridges\ (ucfirst(<name>)),
// So here we make PHPPM\Bridges\Yeast an alias so we can call ppm start --bridge=yeast :)
//
// This file is also marked to be loaded at composer boot
class_alias(Bridge::class, 'PHPPM\\Bridges\\Yeast');

return;


class Yeast extends Bridge {
}