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

use Chevere\Controller\HttpController;
use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use function Chevere\Parameter\booleanParameter;
use Chevere\Parameter\Interfaces\ParametersInterface;
use function Chevere\Parameter\parameters;
use function Chevere\Parameter\stringParameter;

class Locks extends HttpController
{
    public function __construct(
        private DirectoryInterface $directory
    ) {
        parent::__construct();
    }

    public function getResponseParameters(): ParametersInterface
    {
        return parameters(
            lock: booleanParameter()
        )->withAddedOptional(
            stop: booleanParameter()
        );
    }

    public function acceptPost(): ParametersInterface
    {
        return parameters(
            id: stringParameter()
        );
    }

    /**
     * @return array<string, bool>
     */
    public function run(): array
    {
        $id = $this->post()['id'];
        $lockFile = new File(
            $this->directory->path()->getChild('locks/' . $id)
        );
        if (! $lockFile->exists()) {
            return [
                'lock' => false,
            ];
        }
        /** @var array<string, bool> */
        return json_decode($lockFile->getContents(), true);
    }
}
