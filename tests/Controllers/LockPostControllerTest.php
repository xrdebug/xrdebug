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

use Chevere\XrServer\Controllers\LockPostController;
use Clue\React\Sse\BufferedChannel;
use PHPUnit\Framework\TestCase;
use React\Http\Message\ServerRequest;
use function Chevere\Filesystem\directoryForPath;
use function Chevere\Filesystem\fileForPath;

final class LockPostControllerTest extends TestCase
{
    public function test201(): void
    {
        $id = 'b1cabc9a-145f-11ee-be56-0242ac120002';
        $directory = directoryForPath(__DIR__);
        $array = [
            'lock' => true,
            'stop' => false,
        ];
        $encode = json_encode($array);
        $file = fileForPath(__DIR__ . '/' . $id);
        $file->create();
        $file->put($encode);
        $request = new ServerRequest(
            method: 'POST',
            url: '/locks',
            headers: [],
            body: '',
            version: '1.1',
            serverParams: [
                'REMOTE_ADDR' => '0.0.0.0',
            ]
        );
        $channel = new BufferedChannel();
        $cipher = null;
        $controller = new LockPostController(
            $directory,
            $request,
            $channel,
            $cipher
        );
        $controller = $controller->withBody([
            'id' => $id,
        ]);
        $response = $controller->getResponse();
        $decoded = json_decode($file->getContents(), true);
        $this->assertSame($decoded, $response->data());
        $this->assertTrue($file->exists());
        $file->remove();
    }
}
