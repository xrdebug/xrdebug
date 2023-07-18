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

use Chevere\Filesystem\File;
use Chevere\Http\Exceptions\ControllerException;
use Chevere\XrServer\Controllers\LockDeleteController;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\directoryForPath;

final class LockDeleteControllerTest extends TestCase
{
    public function test404(): void
    {
        $id = 'b1cabc9a-145f-11ee-be56-0242ac120002';
        $directory = directoryForPath(__DIR__);
        $path = $directory->path()->getChild($id);
        $file = new File($path);
        $file->removeIfExists();
        $controller = new LockDeleteController($directory);
        $this->expectException(ControllerException::class);
        $this->expectExceptionCode(404);
        $controller->getResponse(id: $id);
    }

    public function test204(): void
    {
        $id = '93683d90-145f-11ee-be56-0242ac120002';
        $directory = directoryForPath(__DIR__);
        $path = $directory->path()->getChild($id);
        $file = new File($path);
        $file->createIfNotExists();
        $controller = new LockDeleteController($directory);
        $response = $controller->getResponse(id: $id);
        $this->assertSame([], $response->array());
        $this->assertFalse($file->exists());
    }
}
