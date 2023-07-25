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

namespace Chevere\XrServer\Controllers;

use Chevere\Attributes\Description;
use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use Chevere\Parameter\Interfaces\ParameterInterface;
use Chevere\XrServer\Constants\UrlPathRegex;
use Chevere\XrServer\Debugger;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\boolean;
use function Chevere\Parameter\string;
use function Safe\json_encode;

#[Status(201)]
#[Description('Create a lock (pause execution)')]
final class LockPostController extends Controller
{
    public function __construct(
        private DirectoryInterface $directory,
        private Debugger $debugger,
        private string $remoteAddress,
    ) {
    }

    public static function acceptResponse(): ParameterInterface
    {
        return arrayp(
            pause: boolean(),
            stop: boolean(),
        );
    }

    public static function acceptBody(): ArrayTypeParameterInterface
    {
        return arrayp(
            id: string(UrlPathRegex::UUID),
        )->withOptional(
            body: string('/.*?/'),
            emote: string(),
            file_line: string(),
            file_path: string(),
            topic: string(),
        );
    }

    protected function run(): array
    {
        $id = $this->body()->cast('id')->string();
        $path = $this->directory->path()->getChild($id);
        $file = new File($path);
        $file->removeIfExists();
        $file->create();
        $data = [
            'pause' => true,
            'stop' => false,
        ];
        $encoded = json_encode($data);
        $file->put($encoded);
        $this->debugger->sendPause(
            $this->body()->toArray(),
            $this->remoteAddress
        );

        return $data;
    }
}
