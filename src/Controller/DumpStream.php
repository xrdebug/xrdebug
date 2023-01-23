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

use Chevere\Controller\HttpController;
use Chevere\Parameter\Interfaces\ParametersInterface;
use function Chevere\Parameter\objectParameter;
use function Chevere\Parameter\parameters;
use function Chevere\Parameter\stringParameter;
use Clue\React\Sse\BufferedChannel;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;

class DumpStream extends HttpController
{
    public function getContainerParameters(): ParametersInterface
    {
        return parameters(
            channel: objectParameter(BufferedChannel::class),
            loop: objectParameter(LoopInterface::class),
            lastEventId: stringParameter(),
            remoteAddress: stringParameter(),
        );
    }

    public function getResponseParameters(): ParametersInterface
    {
        return parameters(
            stream: objectParameter(ThroughStream::class)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $stream = new ThroughStream();
        /** @var BufferedChannel $channel */
        $channel = $this->container()->get('channel');
        /** @var LoopInterface $loop */
        $loop = $this->container()->get('loop');
        $lastEventId = $this->container()->get('lastEventId');
        $remoteAddress = $this->container()->get('remoteAddress');
        $loop->futureTick(function () use ($channel, $stream, $lastEventId) {
            $channel->connect($stream, $lastEventId);
        });
        $message = '{message: "New dump session started [' . $remoteAddress . ']"}';
        $channel->writeMessage($message);
        $stream->on('close', function () use ($stream, $channel, $remoteAddress) {
            $channel->disconnect($stream);
            $message = '{message: "Dump session ended [' . $remoteAddress . ']"}';
            $channel->writeMessage($message);
        });

        return [
            'stream' => $stream,
        ];
    }
}
