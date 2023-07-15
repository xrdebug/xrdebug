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
use phpseclib3\Crypt\Common\SymmetricKey;
use phpseclib3\Crypt\Random;
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

/**
 * @param array<int|string, string> $body
 */
function getBodyActionDump(array $body, string $action): Dump
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
