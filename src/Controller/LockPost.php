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
use Chevere\Http\Controller;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use function Chevere\Parameter\string;
use function Chevere\XrServer\writeToDebugger;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\json_encode;

final class LockPost extends Controller
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

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp(
            id: string()
        );
    }

    /**
     * @return array<string, boolean>
     */
    public function run(): array
    {
        $id = $this->body()['id'];
        $lockFile = new File(
            $this->directory->path()->getChild('locks/' . $id)
        );
        $lockFile->removeIfExists();
        $lockFile->create();
        $data = [
            'lock' => true,
        ];
        $lockFile->put(json_encode($data));
        writeToDebugger(
            request: $this->request,
            channel: $this->channel,
            action: 'pause',
            cipher: $this->cipher,
        );

        return $data;
    }
}
