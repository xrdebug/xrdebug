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

use Chevere\Throwable\Exceptions\LogicException;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\Common\SymmetricKey;
use phpseclib3\Crypt\Random;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Message\message;

function encrypt(SymmetricKey $cipher, string $message, ?string $nonce = null): string
{
    if ($nonce === null) {
        $nonce = Random::string(cipherNonceLength());
    }
    $tag = Random::string(cipherTagLength());
    $cipher = clone $cipher;
    $cipher->setNonce($nonce);
    $cipher->setTag($tag);
    $cipherText = $cipher->encrypt($message);

    return base64_encode($nonce . $cipherText . $cipher->getTag());
}

function decrypt(SymmetricKey $cipher, string $encodedCipherText): string
{
    $decode = base64_decode($encodedCipherText, true);
    if ($decode === false) {
        throw new LogicException(
            message('Unable to decode cipher text')
        );
    }
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
    // vdd(
    //     nonce: base64_encode($nonce),
    //     tag: base64_encode($tag),
    //     cipherText: base64_encode($cipherText),
    // );
    $cipher = clone $cipher;
    $cipher->setNonce($nonce);
    $cipher->setTag($tag);

    return $cipher->decrypt($cipherText);
}

function cipherNonceLength(): int
{
    return 16;
}

function cipherTagLength(): int
{
    return 16;
}

function writeToDebugger(
    ServerRequestInterface $request,
    BufferedChannel $channel,
    string $action = 'message',
    ?AES $cipher = null
): void {
    $body = $request->getParsedBody() ?? [];
    $message = $body['body'] ?? '';
    $message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $message);
    $emote = $body['emote'] ?? '';
    $topic = $body['topic'] ?? '';
    $id = $body['id'] ?? '';
    $file = $body['file_path'] ?? '<file>';
    $line = $body['file_line'] ?? '<line>';
    $fileDisplay = $file;
    $fileDisplayShort = basename($file);
    if ($line !== '') {
        $fileDisplay .= ':' . $line;
        $fileDisplayShort .= ':' . $line;
    }
    /** @var string $dump */
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
    $address = $request->getServerParams()['REMOTE_ADDR'];
    echo "* [{$address} {$action}] {$fileDisplay}\n";
}
