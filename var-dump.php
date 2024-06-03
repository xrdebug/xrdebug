<?php

/*
 * This file is part of xrDebug.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Chevere\VarDump\Outputs\HtmlOutput;

require_once __DIR__ . '/loader.php';

$file = __DIR__ . '/app/src/var-dump.css';
file_put_contents($file, HtmlOutput::CSS);
echo <<<PLAIN
[OK] {$file}

PLAIN;
