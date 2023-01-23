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
use function Chevere\Parameter\booleanParameter;
use Chevere\Parameter\Interfaces\ParametersInterface;
use function Chevere\Parameter\parameters;
use function Chevere\Parameter\stringParameter;
use function Safe\json_encode;

class LockPatch extends Locks
{
    public function __construct(
        private DirectoryInterface $directory
    ) {
        parent::__construct($directory);
    }

    public function getResponseParameters(): ParametersInterface
    {
        return parameters(
            lock: booleanParameter(),
            stop: booleanParameter(),
        );
    }

    public function acceptPost(): ParametersInterface
    {
        return parameters(
            id: stringParameter()
        );
    }

    public function run(): array
    {
        $id = $this->post()['id'];
        $lockFile = new File(
            $this->directory->path()->getChild('locks/' . $id)
        );
        $lockFile->removeIfExists();
        $lockFile->create();
        $data = [
            'lock' => true,
            'stop' => true,
        ];
        $lockFile->put(json_encode($data));

        return $data;
    }
}
