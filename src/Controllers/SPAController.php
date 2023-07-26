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

namespace Chevere\XrDebug\Controllers;

use Chevere\Attributes\Description;
use Chevere\Filesystem\Interfaces\FileInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ParameterInterface;
use function Chevere\Parameter\string;

#[Status(200)]
#[Description('Single page application')]
final class SPAController extends Controller
{
    public function __construct(
        private FileInterface $app
    ) {
    }

    public static function acceptResponse(): ParameterInterface
    {
        return string('/^.*$/m');
    }

    protected function run(): string
    {
        return $this->app->getContents();
    }
}
