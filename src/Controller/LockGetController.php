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
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;

final class LockGetController extends Controller
{
    public function __construct(
        private DirectoryInterface $directory
    ) {
    }

    public static function acceptResponse(): ArrayTypeParameterInterface
    {
        return arrayp(
            lock: boolean()
        )->withOptional(
            stop: boolean()
        );
    }

    public function run(string $id): array
    {
        $path = $this->directory->path()->getChild('locks/' . $id);
        $file = new File($path);
        if (! $file->exists()) {
            return [
                'lock' => false,
            ];
        }
        $contents = $file->getContents();

        return json_decode($contents, true);
    }
}
