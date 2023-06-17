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

use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\routes;
use Chevere\XrServer\Controller\DumpStream;
use Chevere\XrServer\Controller\LockDelete;
use Chevere\XrServer\Controller\LockPatch;
use Chevere\XrServer\Controller\LockPost;
use Chevere\XrServer\Controller\LocksGet;
use Chevere\XrServer\Controller\MessageDump;
use Chevere\XrServer\Controller\SinglePageApp;

return routes(
    route(
        path: '/',
        GET: bind(
            controller: SinglePageApp::class,
            view: 'spa'
        )
    ),
    route(
        path: '/locks',
        POST: LockPost::class,
    ),
    route(
        path: '/locks/{id}',
        GET: LocksGet::class,
        PATCH: LockPatch::class,
        DELETE: LockDelete::class,
    ),
    route(
        path: '/message',
        POST: MessageDump::class,
    ),
    route(
        path: '/dump',
        GET: DumpStream::class,
    ),
);
