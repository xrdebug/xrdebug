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

namespace Chevere\Tests\Controllers;

use Chevere\XrServer\Controllers\SPAController;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\fileForPath;

final class SPAControllerTest extends TestCase
{
    public function test200(): void
    {
        $contents = 'contents';
        $file = fileForPath(__DIR__ . '/app.html');
        $file->createIfNotExists();
        $file->put($contents);
        $controller = new SPAController($file);
        $response = $controller->getResponse();
        $this->assertSame($contents, $response->string());
        $file->remove();
    }
}
