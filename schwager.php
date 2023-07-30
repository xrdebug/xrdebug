<?php

/*
 * This file is part of Chevere.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use function Chevere\Filesystem\fileForPath;
use function Chevere\xrDebug\schwager;

require_once __DIR__ . '/loader.php';

$file = fileForPath(getcwd() . '/schwager.json');
schwager(XRDEBUG_VERSION, $router, $file);
echo $file->path() . PHP_EOL;
