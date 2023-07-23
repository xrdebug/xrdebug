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

namespace Chevere\XrServer;

use Chevere\Http\Controller;
use Chevere\Router\Interfaces\DependenciesInterface;
use Chevere\Router\Interfaces\DispatcherInterface;
use phpseclib3\Crypt\Common\SymmetricKey;
use phpseclib3\Crypt\Random;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Stream\ThroughStream;
use Throwable;
use function Chevere\Http\classStatus;
use function Safe\base64_decode;
use function Safe\json_encode;

function encrypt(SymmetricKey $symmetricKey, string $message, ?string $nonce = null): string
{
    if ($nonce === null) {
        $nonce = Random::string(cipherNonceLength());
    }
    $tag = Random::string(cipherTagLength());
    $symmetricKey = clone $symmetricKey;
    $symmetricKey->setNonce($nonce);
    $symmetricKey->setTag($tag);
    $cipherText = $symmetricKey->encrypt($message);

    return base64_encode($nonce . $cipherText . $symmetricKey->getTag());
}

function decrypt(SymmetricKey $symmetricKey, string $encodedCipherText): string
{
    $decode = base64_decode($encodedCipherText, true);
    $nonce = mb_substr(
        $decode,
        0,
        cipherNonceLength(),
        '8bit'
    );
    $tag = mb_substr(
        $decode,
        -cipherTagLength(),
        null,
        '8bit'
    );
    $cipherText = mb_substr(
        $decode,
        cipherNonceLength(),
        -cipherTagLength(),
        '8bit'
    );
    $symmetricKey = clone $symmetricKey;
    $symmetricKey->setNonce($nonce);
    $symmetricKey->setTag($tag);

    return $symmetricKey->decrypt($cipherText);
}

function cipherNonceLength(): int
{
    return 16;
}

function cipherTagLength(): int
{
    return 16;
}

/**
 * @param array<int|string, string> $body
 */
function getDump(array $body, string $action): Dump
{
    $message = $body['body'] ?? '';
    $message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $message) ?? '';
    $file = $body['file_path'] ?? '<file>';
    $line = $body['file_line'] ?? '<line>';
    $fileDisplay = $file;
    $fileDisplayShort = basename($file);
    if ($line !== '') {
        $fileDisplay .= ':' . $line;
        $fileDisplayShort .= ':' . $line;
    }

    return new Dump(
        message: $message,
        file_path: $file,
        file_line: $line,
        file_display: $fileDisplay,
        file_display_short: $fileDisplayShort,
        emote: $body['emote'] ?? '',
        topic: $body['topic'] ?? '',
        id: $body['id'] ?? '',
        action: $action,
    );
}

/**
 * @param array<string, mixed> $containerMap
 */
function getResponse(
    ServerRequestInterface $request,
    DispatcherInterface $dispatcher,
    DependenciesInterface $dependencies,
    array $containerMap
): ResponseInterface {
    $path = $request->getUri()->getPath();
    $body = $request->getParsedBody() ?? [];

    try {
        $routed = $dispatcher->dispatch($request->getMethod(), $path);
    } catch (Throwable) {
        return new Response(404);
    }
    $containerMap = array_merge($containerMap, [
        'lastEventId' => $request->getHeaderLine('Last-Event-ID'),
        'remoteAddress' => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        'request' => $request,
        'stream' => new ThroughStream(),
    ]);
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
        $controller = $controller->withBody((array) $body);
    }

    try {
        $response = $controller->getResponse(...$routed->arguments());
    } catch (Throwable) {
        return new Response();
    }
    $stream = null;

    try {
        /** @var ThroughStream $stream */
        $stream = $response->object();
    } catch (Throwable) {
    }
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
            $view !== '' => $response->string(),
            default => json_encode($response->array()),
        }
    );
}
