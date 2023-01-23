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

use function Chevere\Filesystem\directoryForPath;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\router;
use function Chevere\Router\routes;
use Chevere\XrServer\Controller\DumpStream;
use Chevere\XrServer\Controller\LockDelete;
use Chevere\XrServer\Controller\LockPatch;
use Chevere\XrServer\Controller\LockPost;
use Chevere\XrServer\Controller\Locks;
use Chevere\XrServer\Controller\MessageDump;
use Chevere\XrServer\Controller\SinglePageApp;

$directory = directoryForPath(__DIR__);

return router(
    web: routes(
        route(
            path: '/',
            GET: bind(new SinglePageApp(), 'spa')
        ),
        route(
            path: '/locks',
            POST: new Locks($directory),
        ),
        route(
            path: '/lock-post',
            POST: new LockPost($directory),
        ),
        route(
            path: '/lock-patch',
            POST: new LockPatch($directory),
        ),
        route(
            path: '/lock-delete',
            POST: new LockDelete($directory),
        ),
        route(
            path: '/message',
            POST: new MessageDump(),
        ),
        route(
            path: '/dump',
            GET: new DumpStream(),
        ),
    )
);
