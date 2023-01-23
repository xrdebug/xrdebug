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

namespace Chevere\XrServer\Controller;

use Chevere\Controller\HttpController;
use Chevere\Parameter\Interfaces\ParametersInterface;
use function Chevere\Parameter\objectParameter;
use function Chevere\Parameter\parameters;
use function Chevere\Parameter\stringParameter;
use function Chevere\XrServer\writeToDebugger;
use Clue\React\Sse\BufferedChannel;
use phpseclib3\Crypt\AES;
use Psr\Http\Message\ServerRequestInterface;

class MessageDump extends HttpController
{
    public function getContainerParameters(): ParametersInterface
    {
        return parameters(
            request: objectParameter(ServerRequestInterface::class),
            channel: objectParameter(BufferedChannel::class),
        )->withAddedOptional(
            cipher: objectParameter(AES::class),
        );
    }

    public function acceptPost(): ParametersInterface
    {
        return parameters()->withAddedOptional(
            body: stringParameter(),
            emote: stringParameter(),
            file_line: stringParameter(),
            file_path: stringParameter(),
            id: stringParameter(),
            topic: stringParameter(),
        );
    }

    /**
     * @return array<string>
     */
    public function run(): array
    {
        /** @var ServerRequestInterface $request */
        $request = $this->container()->get('request');
        /** @var BufferedChannel $channel */
        $channel = $this->container()->get('channel');
        /** @var AES|null $cipher */
        $cipher = $this->container()->get('cipher');
        writeToDebugger(
            request: $request,
            channel: $channel,
            action: 'message',
            cipher: $cipher,
        );

        return [];
    }
}
