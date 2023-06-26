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
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use Chevere\XrServer\Controller\Traits\LockTrait;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use function Safe\json_encode;

#[Status(200)]
final class LockPatchController extends Controller
{
    use LockTrait;

    public function __construct(
        private DirectoryInterface $directory
    ) {
    }

    public static function acceptResponse(): ArrayTypeParameterInterface
    {
        return arrayp(
            lock: boolean(),
            stop: boolean(),
        );
    }

    public function run(string $id): array
    {
        $path = $this->directory->path()->getChild($id);
        $file = new File($path);
        $this->assertExists($file);
        $file->remove();
        $file->create();
        $data = [
            'lock' => true,
            'stop' => true,
        ];
        $json = json_encode($data);
        $file->put($json);

        return $data;
    }
}
