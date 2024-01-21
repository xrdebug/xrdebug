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

namespace Chevere\xrDebug\Controllers;

use Chevere\Http\Attributes\Description;
use Chevere\Http\Attributes\Request;
use Chevere\Http\Attributes\Response;
use Chevere\Http\Controller;
use Chevere\Http\Header;
use Chevere\Http\Status;
use Chevere\Parameter\Interfaces\ArrayParameterInterface;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\xrDebug\Debugger;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\null;
use function Chevere\Parameter\string;

#[Description('Create a debug message')]
#[Request(
    new Header('Content-Type', 'application/json'),
)]
#[Response(
    new Status(204),
)]
final class MessagePostController extends Controller
{
    public function __construct(
        private Debugger $debugger,
        private string $remoteAddress,
    ) {
    }

    public static function acceptBody(): ArrayParameterInterface
    {
        return arrayp()->withOptional(
            body: string('/.*?/'),
            emote: string(),
            file_line: string(),
            file_path: string(),
            id: string(),
            topic: string(),
        )->withOptionalMinimum(1);
    }

    public static function acceptResponse(): ParameterInterface
    {
        return null();
    }

    protected function main(): void
    {
        $this->debugger->sendMessage(
            $this->body()->toArray(),
            $this->remoteAddress
        );
    }
}
