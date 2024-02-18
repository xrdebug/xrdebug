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

use Chevere\Schwager\DocumentSchema;
use Chevere\Schwager\ServerSchema;
use Chevere\Schwager\Spec;
use Chevere\SchwagerHTML\Html;
use function Chevere\Standard\arrayFilterBoth;

require_once __DIR__ . '/loader.php';

$dir = getcwd();
$document = new DocumentSchema(
    api: 'xr',
    name: 'xrDebug API',
    version: XRDEBUG_VERSION
);
$server = new ServerSchema(
    url: '',
    description: 'xrDebug',
);
$spec = new Spec($router, $document, $server);
$array = arrayFilterBoth($spec->toArray(), function ($v, $k) {
    return match (true) {
        $v === null => false,
        $v === [] => false,
        $v === '' => false,
        $k === 'required' && $v === true => false,
        $k === 'regex' && $v === '^.*$' => false,
        $k === 'body' && $v === [
            'type' => 'array#map',
        ] => false,
        default => true,
    };
});
$json = json_encode($array, JSON_PRETTY_PRINT);
$html = new Html($spec);
$file = $dir . '/schwager.json';
file_put_contents($file, $json);
echo <<<PLAIN
[OK] {$file}

PLAIN;
$file = $dir . '/schwager.html';
file_put_contents($file, $html->__toString());
echo <<<PLAIN
[OK] {$file}

PLAIN;
