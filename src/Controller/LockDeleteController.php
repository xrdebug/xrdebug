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

use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;

#[Status(204)]
final class LockDeleteController extends Controller
{
    public function __construct(
        private DirectoryInterface $directory
    ) {
    }

    public function run(string $id): array
    {
        $path = $this->directory->path()->getChild('locks/' . $id);
        $file = new File($path);
        $file->removeIfExists();

        return [];
    }
}
