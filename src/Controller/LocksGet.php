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
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use function Chevere\Parameter\string;

final class LocksGet extends Controller
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

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp(
            id: string()
        );
    }

    /**
     * @return array<string, bool>
     */
    public function run(): array
    {
        $id = $this->body()['id'];
        $lockFile = new File(
            $this->directory->path()->getChild('locks/' . $id)
        );
        if (! $lockFile->exists()) {
            return [
                'lock' => false,
            ];
        }
        $contents = $lockFile->getContents();
        /** @var array<string, bool> */
        return json_decode($contents, true);
    }
}
