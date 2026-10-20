<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\ErrorHandler\DebugClassLoader;

require __DIR__.'/../vendor/autoload.php';

// Deprecations of the class loading: extending a class marked @final or @internal, missing return types...
DebugClassLoader::enable();
