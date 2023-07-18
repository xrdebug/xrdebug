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

use Chevere\Http\Exceptions\ControllerException;
use Chevere\XrServer\Controllers\LockGetController;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\directoryForPath;
use function Chevere\Filesystem\fileForPath;

final class LockGetControllerTest extends TestCase
{
    public function test404(): void
    {
        $id = 'b1cabc9a-145f-11ee-be56-0242ac120002';
        $directory = directoryForPath(__DIR__);
        $controller = new LockGetController($directory);
        $this->expectException(ControllerException::class);
        $this->expectExceptionCode(404);
        $controller->getResponse(id: $id);
    }

    public function test200(): void
    {
        $id = '93683d90-145f-11ee-be56-0242ac120002';
        $array = [
            'lock' => true,
            'stop' => false,
        ];
        $encode = json_encode($array);
        $file = fileForPath(__DIR__ . '/' . $id);
        $file->create();
        $file->put($encode);
        $directory = directoryForPath(__DIR__);
        $controller = new LockGetController($directory);
        $response = $controller->getResponse(id: $id);
        $this->assertSame($array, $response->array());
        $file->remove();
    }
}
