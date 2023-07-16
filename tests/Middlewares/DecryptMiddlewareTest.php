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

namespace Chevere\Tests\Middlewares;

use Chevere\Tests\src\Traits\Psr17Trait;
use Chevere\XrServer\Middlewares\DecryptMiddleware;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Chevere\Writer\streamTemp;
use function Chevere\XrServer\encrypt;

final class DecryptMiddlewareTest extends TestCase
{
    use Psr17Trait;

    public function testNoCipher(): void
    {
        $middleware = new DecryptMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($request);
        $middleware->process($request, $handler);
    }

    public function testCipher(): void
    {
        $message = '{"action":"message"}';
        $symmetricKey = Random::string(32);
        $cipher = new AES('gcm');
        $cipher->setKey($symmetricKey);
        $middleware = new DecryptMiddleware($cipher);
        $function = function ($request) use ($message) {
            $this->assertSame($message, $request->getBody()->__toString());
        };
        $handler = $this->getRequestHandler($function);
        $cipherBody = encrypt($cipher, $message);
        $request = $this->getRequest()
            ->withBody(streamTemp($cipherBody));
        $middleware->process($request, $handler);
    }
}
