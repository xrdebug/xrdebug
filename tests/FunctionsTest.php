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

use Chevere\Filesystem\Interfaces\FileInterface;
use Chevere\Router\Dependencies;
use Chevere\Router\Dispatcher;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use Chevere\Router\Interfaces\RoutesInterface;
use Chevere\Tests\src\Traits\CipherTrait;
use Chevere\Tests\src\Traits\DirectoryTrait;
use Chevere\Tests\src\Traits\Psr17Trait;
use Chevere\Throwable\Exceptions\LogicException;
use Chevere\XrDebug\Controllers\PausePostController;
use Chevere\XrDebug\Controllers\SPAController;
use Chevere\XrDebug\Controllers\StreamController;
use Chevere\XrDebug\Debugger;
use Clue\React\Sse\BufferedChannel;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;
use function Chevere\Http\classStatus;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;
use function Chevere\XrDebug\decrypt;
use function Chevere\XrDebug\encrypt;
use function Chevere\XrDebug\getArguments;
use function Chevere\XrDebug\getDump;
use function Chevere\XrDebug\getResponse;

final class FunctionsTest extends TestCase
{
    use CipherTrait;
    use Psr17Trait;
    use DirectoryTrait;

    public function testEncryptDecrypt(): void
    {
        $symmetricKey = $this->getCipher();
        $message = "I'm the miggida miggida miggida miggida Mac Daddy";
        $encrypt = encrypt($symmetricKey, $message);
        $decrypt = decrypt($symmetricKey, $encrypt);
        $this->assertSame($message, $decrypt);
    }

    public function dumpDataProvider(): array
    {
        return [
            [
                [],
                [
                    'message' => '',
                    'file_path' => '<file>',
                    'file_line' => '<line>',
                    'file_display' => '<file>:<line>',
                    'file_display_short' => '<file>:<line>',
                    'emote' => '',
                    'topic' => '',
                    'id' => '',
                    'action' => 'action',
                ],
            ],
            [
                [
                    'body' => '<script>alert("xss")</script>',
                    'file_path' => '',
                    'file_line' => '',
                ],
                [
                    'message' => '',
                    'file_path' => '',
                    'file_line' => '',
                    'file_display' => '',
                    'file_display_short' => '',
                    'emote' => '',
                    'topic' => '',
                    'id' => '',
                    'action' => 'action',
                ],
            ],
            [
                [
                    'id' => 'id',
                    'body' => 'body',
                    'file_path' => 'file_path',
                    'file_line' => 'file_line',
                    'emote' => 'emote',
                    'topic' => 'topic',
                ],
                [
                    'message' => 'body',
                    'file_path' => 'file_path',
                    'file_line' => 'file_line',
                    'file_display' => 'file_path:file_line',
                    'file_display_short' => 'file_path:file_line',
                    'emote' => 'emote',
                    'topic' => 'topic',
                    'id' => 'id',
                    'action' => 'action',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dumpDataProvider
     */
    public function testGetDump(array $body, array $expected): void
    {
        $dump = getDump($body, 'action');
        $this->assertSame($expected, $dump->toArray());
    }

    public function testGetResponse404(): void
    {
        $request = $this->getRequest();
        $dependencies = $this->createMock(DependenciesInterface::class);
        $dispatcher = $this->createMock(DispatcherInterface::class);
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($request->getMethod(), $request->getUri()->getPath())
            ->willThrowException(new Exception());
        $response = getResponse(
            $request,
            $dispatcher,
            $dependencies,
            []
        );
        $this->assertSame(404, $response->getStatusCode());
    }

    public function responseDataProvider(): array
    {
        $request = $this->getRequest();
        $app = $this->createMock(FileInterface::class);
        $app
            ->expects($this->once())
            ->method('getContents')
            ->willReturn('app');
        $directory = $this->getWritableDirectory();
        $debugger = $this->createMock(Debugger::class);
        $remoteAddress = 'remoteAddress';
        $stream = new ThroughStream();

        return [
            [
                $request,
                routes(
                    route(
                        path: '/',
                        GET: bind(
                            controller: SPAController::class,
                            view: 'spa'
                        )
                    )
                ),
                SPAController::class,
                [
                    'app' => $app,
                ],
                'text/html',
                'app',
            ],
            [
                $request,
                routes(
                    route(
                        path: '/',
                        GET: bind(
                            controller: StreamController::class,
                        )
                    )
                ),
                StreamController::class,
                [
                    'channel' => $this->createMock(BufferedChannel::class),
                    'loop' => $this->createMock(LoopInterface::class),
                    'stream' => $stream,
                    'lastEventId' => '12345',
                    'remoteAddress' => $remoteAddress,
                ],
                'text/event-stream',
                $stream,
            ],
            [
                $request
                    ->withMethod('POST')
                    ->withParsedBody([
                        'id' => 'b1cabc9a-145f-11ee-be56-0242ac120002',
                    ]),
                routes(
                    route(
                        path: '/',
                        POST: bind(
                            controller: PausePostController::class,
                        )
                    )
                ),
                PausePostController::class,
                [
                    'directory' => $directory,
                    'debugger' => $debugger,
                    'remoteAddress' => $remoteAddress,
                ],
                'text/json',
                '{"pause":true,"stop":false}',
            ],
        ];
    }

    /**
     * @dataProvider responseDataProvider
     */
    public function testGetResponse200(
        RequestInterface $request,
        RoutesInterface $routes,
        string $controllerName,
        array $container,
        string $contentType,
        mixed $content
    ): void {
        $dependencies = new Dependencies($routes);
        $dispatcher = $this->getDispatcher($routes);
        $response = getResponse(
            $request,
            $dispatcher,
            $dependencies,
            $container
        );
        $status = classStatus($controllerName);
        $this->assertSame($status->primary, $response->getStatusCode());
        $this->assertSame($contentType, $response->getHeaderLine('Content-Type'));
        if ($content instanceof ThroughStream) {
            return;
        }
        $this->assertSame($content, $response->getBody()->__toString());
    }

    public function testGetControllerArguments(): void
    {
        $routes = routes(
            route(
                path: '/',
                GET: bind(
                    controller: SPAController::class,
                )
            )
        );
        $dependencies = new Dependencies($routes);
        $pass = [
            'app' => $this->createMock(FileInterface::class),
        ];
        $arguments = getArguments($dependencies, SPAController::class, $pass);
        $this->assertSame($pass, $arguments);
        $this->expectException(LogicException::class);
        $arguments = getArguments($dependencies, SPAController::class, []);
    }

    private function getDispatcher(RoutesInterface $routes): DispatcherInterface
    {
        return new Dispatcher(
            router($routes)->routeCollector()
        );
    }
}
