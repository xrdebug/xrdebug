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

use Chevere\Action\Traits\NoStrictActionTrait;
use Chevere\Filesystem\Interfaces\FileInterface;
use Chevere\Http\Attributes\Status;
use Chevere\Http\Controller;
use Chevere\Parameter\Interfaces\ArrayTypeParameterInterface;
use function Chevere\Parameter\arrayp;
use function Chevere\Parameter\string;

#[Status(200)]
final class SPAController extends Controller
{
    // use NoStrictActionTrait;

    public function __construct(
        private FileInterface $app
    ) {
    }

    public static function acceptResponse(): ArrayTypeParameterInterface
    {
        return arrayp(
            app: string('/^.*$/m'),
        );
    }

    protected function run(): array
    {
        return [
            'app' => $this->app->getContents(),
        ];
    }
}
