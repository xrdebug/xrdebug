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

namespace Chevere\XrServer\Controllers;

use Chevere\Attributes\Description;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\Writer\Interfaces\WriterInterface;
use Clue\React\Sse\BufferedChannel;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;
use function Chevere\Parameter\object;

#[Status(200)]
#[Description('Debug stream')]
final class DebugStreamController extends Controller
{
    public function __construct(
        private BufferedChannel $channel,
        private LoopInterface $loop,
        private string $lastEventId,
        private string $remoteAddress,
        private WriterInterface $logger,
    ) {
    }

    public static function acceptResponse(): ParameterInterface
    {
        return object(ThroughStream::class);
    }

    protected function run(): ThroughStream
    {
        $stream = new ThroughStream();
        $channel = $this->channel;
        $lastEventId = $this->lastEventId;
        $remoteAddress = $this->remoteAddress;
        $this->loop->futureTick(
            function () use ($channel, $stream, $lastEventId) {
                $channel->connect($stream, $lastEventId);
            }
        );
        $message = '{message: "New dump session started [' . $remoteAddress . ']"}';
        $channel->writeMessage($message);
        $stream->on(
            'close',
            function () use ($stream, $channel, $remoteAddress) {
                $channel->disconnect($stream);
                $message = '{message: "Dump session ended [' . $remoteAddress . ']"}';
                $channel->writeMessage($message);
            }
        );

        return $stream;
    }
}
