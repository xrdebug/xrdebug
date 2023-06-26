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

namespace Chevere\Tests\Controller;

use Chevere\Filesystem\File;
use Chevere\Http\Exceptions\ControllerException;
use Chevere\XrServer\Controller\LockPatchController;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\directoryForPath;

final class LockPatchControllerTest extends TestCase
{
    public function test404(): void
    {
        $id = 'not-found';
        $directory = directoryForPath(__DIR__);
        $path = $directory->path()->getChild($id);
        $file = new File($path);
        $controller = new LockPatchController($directory);
        $this->expectException(ControllerException::class);
        $this->expectExceptionCode(404);
        $controller->getResponse(id: $id);
    }

    public function test200(): void
    {
        $id = 'found';
        $directory = directoryForPath(__DIR__);
        $path = $directory->path()->getChild($id);
        $file = new File($path);
        $file->createIfNotExists();
        $controller = new LockPatchController($directory);
        $response = $controller->getResponse(id: $id);
        $decoded = json_decode($file->getContents(), true);
        $this->assertSame($decoded, $response->data());
        $this->assertTrue($file->exists());
        $file->remove();
    }
}
