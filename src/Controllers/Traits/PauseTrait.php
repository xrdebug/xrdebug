<?php

/*
 * This file is part of xrDebug.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Chevere\xrDebug\Controllers\Traits;

use Chevere\Filesystem\Exceptions\FileNotExistsException;
use Chevere\Filesystem\Interfaces\FileInterface;
use Chevere\Http\Exceptions\ControllerException;

trait PauseTrait
{
    private function assertExists(FileInterface $file): void
    {
        try {
            $file->assertExists();
        } catch (FileNotExistsException) {
            throw new ControllerException(code: 404);
        }
    }
}
