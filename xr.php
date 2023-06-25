#!/usr/bin/env php
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

foreach (['/', '/../../../'] as $path) {
    $autoload = __DIR__ . $path . 'vendor/autoload.php';
    if (stream_resolve_include_path($autoload)) {
        require $autoload;

        break;
    }
}

use Chevere\Http\Controller;
use Chevere\Router\Dependencies;
use Chevere\Router\Dispatcher;
use Chevere\ThrowableHandler\ThrowableHandler;
use Chevere\Writer\StreamWriter;
use Chevere\Writer\Writers;
use Chevere\Writer\WritersInstance;
use Chevere\XrServer\Build;
use Clue\React\Sse\BufferedChannel;
use Colors\Color;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\Random;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Stream\ThroughStream;
use samejack\PHP\ArgvParser;
use function Chevere\Filesystem\directoryForPath;
use function Chevere\Filesystem\fileForPath;
use function Chevere\Http\classStatus;
use function Chevere\Router\router;
use function Chevere\ThrowableHandler\handleAsConsole;
use function Chevere\Writer\streamFor;
use function Safe\json_encode;

include __DIR__ . '/meta.php';

new WritersInstance(
    (new Writers())
        ->with(
            new StreamWriter(
                streamFor('php://output', 'w')
            )
        )
        ->withError(
            new StreamWriter(
                streamFor('php://stderr', 'w')
            )
        )
);
set_error_handler(ThrowableHandler::ERROR_AS_EXCEPTION);
register_shutdown_function(ThrowableHandler::SHUTDOWN_ERROR_AS_EXCEPTION);
set_exception_handler(ThrowableHandler::CONSOLE);

$color = new Color();
echo $color(file_get_contents(__DIR__ . '/logo'))->cyan() . "\n";
echo $color(strtr('XR Debug %v (%c) by Rodolfo Berrios', [
    '%v' => XR_SERVER_VERSION,
    '%c' => XR_SERVER_CODENAME,
]))->green() . "\n\n";
$options = (new ArgvParser())->parseConfigs();
if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    echo implode("\n", [
        '-p Port (default 27420)',
        '-e Enable end-to-end encryption',
        '-k Symmetric key (for -e option)',
        '-v Enable sign verification',
        '-s Private key (for -v option)',
        '-c Cert file for TLS',
        '',
    ]);
    exit(0);
}
$host = '0.0.0.0';
$port = $options['p'] ?? '27420';
$cert = $options['c'] ?? null;
$isEncryptionEnabled = $options['e'] ?? false;
$isSignVerificationEnabled = $options['v'] ?? false;
$scheme = isset($cert) ? 'tls' : 'tcp';
$uri = "{$scheme}://{$host}:{$port}";
$context = $scheme === 'tcp'
    ? []
    : [
        'tls' => [
            'local_cert' => $cert,
        ],
    ];
$cipher = null;
if ($isEncryptionEnabled) {
    $symmetricKey = array_key_exists('k', $options) ? $options['k'] : null;
    if ($symmetricKey === null) {
        $symmetricKey = Random::string(32);
        echo $color('INFO: Generated encryption key (empty -k)')->magenta() . "\n";
    } else {
        $symmetricKey = base64_decode($symmetricKey, true);
    }
    $cipher = new AES('gcm');
    $cipher->setKey($symmetricKey);
    $encryptionKeyDisplay = base64_encode($symmetricKey);
    echo <<<PLAIN
    🔐 ENCRYPTION KEY
    {$encryptionKeyDisplay}


    PLAIN;
}
$privateKey = null;
if ($isSignVerificationEnabled) {
    $privateKey = array_key_exists('s', $options) ? $options['s'] : null;
    if ($privateKey === null) {
        $privateKey = EC::createKey('ed25519');
        echo $color('INFO: Generated private key (empty -s)')->magenta() . "\n";
    } else {
        $privateKey = EC::load($privateKey);
    }
    $privateKeyDisplay = $privateKey->toString('PKCS8');
    echo <<<PLAIN
    🔏 PRIVATE KEY
    {$privateKeyDisplay}


    PLAIN;
}

$directory = directoryForPath(__DIR__);

try {
    $directory->getChild('locks/')->removeContents();
} catch (Throwable) {
}
$build = new Build(
    $directory->getChild('app/src/'),
    XR_SERVER_VERSION,
    XR_SERVER_CODENAME,
    $isEncryptionEnabled,
);
$app = fileForPath($directory->getChild('app/build/')->path()->__toString() . 'en.html');
$app->removeIfExists();
$app->create();
$app->put($build->html());
$routes = include 'routes.php';
$dependencies = new Dependencies($routes);
$router = router($routes);
$routeCollector = $router->routeCollector();
$dispatcher = new Dispatcher($routeCollector);
$loop = Loop::get();
$channel = new BufferedChannel();
$handler = function (ServerRequestInterface $request) use (
    $channel,
    $loop,
    $cipher,
    $dispatcher,
    $app,
    $dependencies,
    $directory
) {
    try {
        $path = $request->getUri()->getPath();
        $body = $request->getParsedBody() ?? [];

        try {
            $routed = $dispatcher->dispatch($request->getMethod(), $path);
        } catch (Throwable) {
            return new Response(404);
        }
        $containerMap = [
            'app' => $app,
            'directory' => $directory,
            'request' => $request,
            'channel' => $channel,
            'cipher' => $cipher,
            'loop' => $loop,
            'lastEventId' => $request->getHeaderLine('Last-Event-ID'),
            'remoteAddress' => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ];
        $view = $routed->bind()->view();
        $controllerName = $routed->bind()->controllerName()->__toString();
        $controllerArguments = [];

        try {
            foreach ($dependencies->get($controllerName)->keys() as $key) {
                $controllerArguments[$key] = $containerMap[$key];
            }
        } catch (Throwable) {
        }
        /** @var Controller $controller */
        $controller = new $controllerName(...$controllerArguments);
        if ($request->getMethod() === 'POST') {
            $controller = $controller->withBody($body);
        }

        try {
            $response = $controller->getResponse(...$routed->arguments());
        } catch (Throwable $e) {
            handleAsConsole($e);
        }
        $stream = $response->data()['stream'] ?? null;
        $isStream = $stream instanceof ThroughStream;
        $statuses = classStatus($controllerName);

        return new Response(
            $statuses->primary,
            [
                'Content-Type' => match (true) {
                    $isStream => 'text/event-stream',
                    $view === 'spa/GET' => 'text/html',
                    default => 'text/json',
                },
            ],
            match (true) {
                $isStream => $stream,
                $view !== '' => $response->data()['app'],
                default => json_encode($response->data()),
            }
        );
    } catch (Throwable $e) {
        handleAsConsole($e);
    }
};
$http = new HttpServer(
    $loop,
    new StreamingRequestMiddleware(),
    new LimitConcurrentRequestsMiddleware(100),
    new RequestBodyBufferMiddleware(8 * 1024 * 1024),
    new RequestBodyParserMiddleware(100 * 1024, 1),
    $handler
);
$socket = new \React\Socket\SocketServer(
    $uri,
    $context,
    $loop
);
$http->listen($socket);
$socket->on('error', 'printf');
$scheme = parse_url($socket->getAddress(), PHP_URL_SCHEME);
$httpAddress = strtr(
    $socket->getAddress(),
    [
        'tls' => 'https',
        'tcp' => 'http',
    ]
);
echo <<<PLAIN
    Server listening on {$scheme} {$httpAddress}
    Press Ctrl+C to quit
    --

    PLAIN;
$loop->run();
