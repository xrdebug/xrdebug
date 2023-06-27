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

namespace Chevere\XrServer\Controllers;

use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\string;
use function Chevere\XrServer\writeToDebugger;

#[Status(201)]
final class MessageDumpController extends Controller
{
    public function __construct(
        private ServerRequestInterface $request,
        private BufferedChannel $channel,
        private ?AES $cipher = null
    ) {
    }

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp()->withOptional(
            body: string(),
            emote: string(),
            file_line: string(),
            file_path: string(),
            id: string(),
            topic: string(),
        );
    }

    protected function run(): array
    {
        writeToDebugger(
            request: $this->request,
            channel: $this->channel,
            action: 'message',
            cipher: $this->cipher,
        );

        return [];
    }
}
