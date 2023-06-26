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

use Chevere\Attribute\StringRegex;
use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\XrServer\Constant\UrlPathRegex;
use Chevere\XrServer\Controller\Traits\LockTrait;

#[Status(204, 404)]
final class LockDeleteController extends Controller
{
    use LockTrait;

    public function __construct(
        private DirectoryInterface $directory
    ) {
    }

    protected function run(
        #[StringRegex(UrlPathRegex::UUID)]
        string $id
    ): array {
        $path = $this->directory->path()->getChild($id);
        $file = new File($path);
        $this->assertExists($file);
        $file->remove();

        return [];
    }
}
