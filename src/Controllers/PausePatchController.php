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
use Chevere\Attributes\Regex;
use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\xrDebug\Constants\UrlPathRegex;
use Chevere\xrDebug\Controllers\Traits\PauseTrait;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use function Safe\json_encode;

#[Status(200)]
#[Description('Update a pause to stop execution')]
final class PausePatchController extends Controller
{
    use PauseTrait;

    public function __construct(
        private DirectoryInterface $directory
    ) {
    }

    public static function acceptResponse(): ParameterInterface
    {
        return arrayp(
            stop: boolean(),
        );
    }

    protected function run(
        #[Regex(UrlPathRegex::UUID)]
        string $id
    ): array {
        $path = $this->directory->path()->getChild($id);
        $file = new File($path);
        $this->assertExists($file);
        $file->remove();
        $file->create();
        $data = [
            'stop' => true,
        ];
        $json = json_encode($data);
        $file->put($json);

        return $data;
    }
}
