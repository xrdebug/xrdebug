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
use Chevere\XrServer\Constant\UrlPathRegex;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use function Chevere\Parameter\string;
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
            lock: boolean(),
            stop: boolean(),
        );
    }

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp(
            id: string(UrlPathRegex::UUID)
        );
    }

    protected function run(): array
    {
        /** @var string $id */
        $id = $this->body()['id'];
        $path = $this->directory->path()->getChild($id);
        $file = new File($path);
        $file->removeIfExists();
        $file->create();
        $data = [
            'lock' => true,
            'stop' => false,
        ];
        $encoded = json_encode($data);
        $file->put($encoded);
        writeToDebugger(
            request: $this->request,
            channel: $this->channel,
            action: 'pause',
            cipher: $this->cipher,
        );

        return $data;
    }
}
