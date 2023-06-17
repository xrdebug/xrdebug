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

use Chevere\Http\Controller;
use function Chevere\Parameter\arrayp;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use function Chevere\Parameter\object;
use Clue\React\Sse\BufferedChannel;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;

class DumpStream extends Controller
{
    public function __construct(
        private BufferedChannel $channel,
        private LoopInterface $loop,
        private string $lastEventId,
        private string $remoteAddress,
    ) {
    }

    public static function acceptResponse(): ArrayTypeParameterInterface
    {
        return arrayp(
            stream: object(ThroughStream::class)
        );
    }

    /**
     * @return array<string, ThroughStream>
     */
    public function run(): array
    {
        $stream = new ThroughStream();
        $channel = $this->channel;
        $loop = $this->loop;
        $lastEventId = $this->lastEventId;
        $remoteAddress = $this->remoteAddress;
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
