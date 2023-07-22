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

use phpseclib3\Crypt\Common\SymmetricKey;
use phpseclib3\Crypt\Random;
use function Safe\base64_decode;

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
