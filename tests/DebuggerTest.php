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

namespace Chevere\Tests;

use Chevere\Writer\StreamWriter;
use Chevere\XrServer\Debugger;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;
use PHPUnit\Framework\TestCase;
use function Chevere\Writer\streamTemp;
use function Chevere\XrServer\decrypt;

final class DebuggerTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            ['message', 'sendMessage'],
            ['pause', 'sendPause'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSend(string $action, string $method): void
    {
        $callback = function (string $json) use ($action) {
            $array = json_decode($json, true);
            $this->assertSame($action, $array['action']);
        };
        $channel = $this->createMock(BufferedChannel::class);
        $channel
            ->expects($this->once())
            ->method('writeMessage')
            ->will($this->returnCallback($callback));
        $writer = new StreamWriter(streamTemp());
        $debugger = new Debugger($channel, $writer);
        $debugger->{$method}([], 'localhost');
        $this->assertSame("* [localhost {$action}] <file>:<line>\n", $writer->__toString());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCipher(string $action, string $method): void
    {
        $symmetricKey = Random::string(32);
        $cipher = new AES('gcm');
        $cipher->setKey($symmetricKey);
        $callback = function (string $json) use ($cipher, $action) {
            $json = decrypt($cipher, $json);
            $array = json_decode($json, true);
            $this->assertSame($action, $array['action']);
        };
        $channel = $this->createMock(BufferedChannel::class);
        $channel
            ->expects($this->once())
            ->method('writeMessage')
            ->will($this->returnCallback($callback));
        $writer = new StreamWriter(streamTemp());
        $debugger = new Debugger($channel, $writer, $cipher);
        $debugger->{$method}([], 'localhost');
    }
}
