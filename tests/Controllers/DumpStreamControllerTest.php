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

use Chevere\XrServer\Controllers\DumpStreamController;
use Clue\React\Sse\BufferedChannel;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;

final class DumpStreamControllerTest extends TestCase
{
    public function test200(): void
    {
        $buffered = '';
        $isConnected = null;
        $channel = $this->getMockBuilder(BufferedChannel::class)->getMock();
        $channel
            ->expects($this->any())
            ->method('writeMessage')
            ->will(
                $this->returnCallback(function ($data) use (&$buffered) {
                    $buffered .= $data;
                })
            );
        $channel
            ->expects($this->any())
            ->method('connect')
            ->will(
                $this->returnCallback(function ($stream, $lastEventId) use (&$isConnected) {
                    $isConnected = true;
                })
            );
        $channel
            ->expects($this->any())
            ->method('disconnect')
            ->will(
                $this->returnCallback(function () use (&$isConnected) {
                    $isConnected = false;
                })
            );
        /** @var BufferedChannel $channel */
        $loop = $this->getMockBuilder(LoopInterface::class)->getMock();
        $loop
            ->expects($this->any())
            ->method('futureTick')
            ->will(
                $this->returnCallback(function ($callback) {
                    $callback();
                })
            );
        /** @var LoopInterface $loop */
        $stream = new ThroughStream();
        $lastEventId = '1234567890';
        $remoteAddress = '0.0.0.0';
        $controller = new DumpStreamController(
            $channel,
            $loop,
            $stream,
            $lastEventId,
            $remoteAddress
        );
        $this->assertNull($isConnected);
        $this->assertSame($stream, $controller->getResponse()->array()['stream']);
        $this->assertTrue($isConnected);
        $sessionStart = '{message: "New dump session started [' . $remoteAddress . ']"}';
        $this->assertSame($sessionStart, $buffered);
        $buffered = '';
        $stream->close();
        $this->assertFalse($isConnected);
        $sessionEnd = '{message: "Dump session ended [' . $remoteAddress . ']"}';
        $this->assertSame($sessionEnd, $buffered);
    }
}
