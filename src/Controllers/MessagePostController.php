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

use Chevere\Attributes\Description;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\xrDebug\Debugger;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\null;
use function Chevere\Parameter\string;

#[Status(200)]
#[Description('Create a debug message')]
final class MessagePostController extends Controller
{
    public function __construct(
        private Debugger $debugger,
        private string $remoteAddress,
    ) {
    }

    public static function acceptBody(): ArrayTypeParameterInterface
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

    protected function run(): void
    {
        $this->debugger->sendMessage(
            $this->body()->toArray(),
            $this->remoteAddress
        );
    }
}
