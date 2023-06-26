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

namespace Chevere\XrServer\Controller;

use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use function Chevere\XrServer\writeToDebugger;
use function Safe\json_encode;

#[Status(201)]
final class LockPostController extends Controller
{
    public function __construct(
        private DirectoryInterface $directory,
        private ServerRequestInterface $request,
        private BufferedChannel $channel,
        private ?AES $cipher = null
    ) {
    }

    public static function acceptResponse(): ArrayTypeParameterInterface
    {
        return arrayp(
            lock: boolean()
        );
    }

    public function run(string $id): array
    {
        $path = $this->directory->path()->getChild('locks/' . $id);
        $file = new File($path);
        $file->removeIfExists();
        $file->create();
        $data = [
            'lock' => true,
        ];
        $file->put(json_encode($data));
        writeToDebugger(
            request: $this->request,
            channel: $this->channel,
            action: 'pause',
            cipher: $this->cipher,
        );

        return $data;
    }
}
