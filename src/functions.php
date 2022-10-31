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

use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Random;
use Psr\Http\Message\ServerRequestInterface;

function encrypt(AES $cipher, string $message, ?string $iv = null): string
{
    if ($iv === null) {
        $iv = Random::string(16);
    }
    $cipher = clone $cipher;
    $cipher->setIV($iv);

    return base64_encode($iv . $cipher->encrypt($message));
}

function decrypt(AES $cipher, string $encodedCipherText): string
{
    $decode = base64_decode($encodedCipherText);
    $iv = mb_substr(
        $decode,
        0,
        16,
        '8bit'
    );
    $cipherText = mb_substr(
        $decode,
        16,
        null,
        '8bit'
    );
    $cipher = clone $cipher;
    $cipher->setIV($iv);

    return $cipher->decrypt($cipherText);
}

function writeToDebugger(
    ServerRequestInterface $request,
    BufferedChannel $channel,
    string $action = 'message',
    ?AES $cipher = null
): void {
    $address = $request->getServerParams()['REMOTE_ADDR'];
    $body = $request->getParsedBody() ?? [];
    $message = $body['body'] ?? '';
    $message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $message);
    $emote = $body['emote'] ?? '';
    $topic = $body['topic'] ?? '';
    $id = $body['id'] ?? '';
    $file = $body['file_path'] ?? '';
    $line = $body['file_line'] ?? '';
    $fileDisplay = $file;
    $fileDisplayShort = basename($file);
    if ($line !== '') {
        $fileDisplay .= ':' . $line;
        $fileDisplayShort .= ':' . $line;
    }
    $dump = json_encode([
        'message' => $message,
        'file_path' => $file,
        'file_line' => $line,
        'file_display' => $fileDisplay,
        'file_display_short' => $fileDisplayShort,
        'emote' => $emote,
        'topic' => $topic,
        'id' => $id,
        'action' => $action,
    ]);
    $channel->writeMessage(
        $cipher === null
            ? $dump
            : encrypt($cipher, $dump)
    );
    echo "* [$address $action] $fileDisplay\n";
}
