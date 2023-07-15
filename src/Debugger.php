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

use Chevere\Writer\Interfaces\WriterInterface;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;

final class Debugger
{
    public function __construct(
        private BufferedChannel $channel,
        private WriterInterface $logger,
        private ?AES $cipher = null,
    ) {
    }

    /**
     * @param array<int|string, string> $body
     */
    public function sendMessage(
        array $body, //  $request->getParsedBody() ?? []
        string $address, // $request->getServerParams()['REMOTE_ADDR']
    ): void {
        $this->channelWrite($body, $address, 'message');
    }

    /**
     * @param array<int|string, string> $body
     */
    public function sendPause(
        array $body,
        string $address,
    ): void {
        $this->channelWrite($body, $address, 'pause');
    }

    /**
     * @param array<int|string, string> $body
     */
    private function channelWrite(
        array $body,
        string $address,
        string $action,
    ): void {
        $dump = $this->getDump($body, $action);
        $json = $dump->toJson();
        if ($this->cipher !== null) {
            $json = encrypt($this->cipher, $json);
        }
        $this->channel->writeMessage($json);
        $this->logger->write(
            "* [{$address} {$action}] {$dump->file_display}\n"
        );
    }

    /**
     * @param array<int|string, string> $body
     */
    private function getDump(array $body, string $action): Dump
    {
        $message = $body['body'] ?? '';
        $message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $message) ?? '';
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

        return new Dump(
            message: $message,
            file_path: $file,
            file_line: $line,
            file_display: $fileDisplay,
            file_display_short: $fileDisplayShort,
            emote: $emote,
            topic: $topic,
            id: $id,
            action: $action,
        );
    }
}
