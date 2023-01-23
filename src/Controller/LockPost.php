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
use function Chevere\Parameter\booleanParameter;
use Chevere\Parameter\Interfaces\ParametersInterface;
use function Chevere\Parameter\objectParameter;
use function Chevere\Parameter\parameters;
use function Chevere\Parameter\stringParameter;
use function Chevere\XrServer\writeToDebugger;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\json_encode;

class LockPost extends Locks
{
    public function __construct(
        private DirectoryInterface $directory
    ) {
        parent::__construct($directory);
    }

    public function getResponseParameters(): ParametersInterface
    {
        return parameters(
            lock: booleanParameter()
        );
    }

    public function getContainerParameters(): ParametersInterface
    {
        return parameters(
            request: objectParameter(ServerRequestInterface::class),
            channel: objectParameter(BufferedChannel::class),
        )->withAddedOptional(
            cipher: objectParameter(AES::class),
        );
    }

    public function acceptPost(): ParametersInterface
    {
        return parameters(
            id: stringParameter()
        );
    }

    public function run(): array
    {
        $id = $this->post()['id'];
        $lockFile = new File(
            $this->directory->path()->getChild('locks/' . $id)
        );
        $lockFile->removeIfExists();
        $lockFile->create();
        $data = [
            'lock' => true,
        ];
        $lockFile->put(json_encode($data));
        /** @var ServerRequestInterface $request */
        $request = $this->container()->get('request');
        /** @var BufferedChannel $channel */
        $channel = $this->container()->get('channel');
        /** @var AES|null $cipher */
        $cipher = $this->container()->get('cipher');
        writeToDebugger(
            request: $request,
            channel: $channel,
            action: 'pause',
            cipher: $cipher,
        );

        return $data;
    }
}
