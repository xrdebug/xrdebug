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

use Chevere\XrServer\Controllers\DumpStreamController;
use Chevere\XrServer\Controllers\LockDeleteController;
use Chevere\XrServer\Controllers\LockGetController;
use Chevere\XrServer\Controllers\LockPatchController;
use Chevere\XrServer\Controllers\LockPostController;
use Chevere\XrServer\Controllers\MessagePostController;
use Chevere\XrServer\Controllers\SPAController;
use Chevere\XrServer\Middlewares\DecryptMiddleware;
use Chevere\XrServer\Middlewares\VerifySignatureMiddleware;
use function Chevere\Router\bind;
use function Chevere\Router\route;
use function Chevere\Router\routes;

return routes(
    route(
        path: '/',
        GET: bind(
            controller: SPAController::class,
            view: 'spa'
        )
    ),
    route(
        path: '/locks',
        POST: bind(
            LockPostController::class,
            VerifySignatureMiddleware::class,
        ),
    ),
    route(
        path: '/locks/{id}',
        GET: bind(
            LockGetController::class,
            VerifySignatureMiddleware::class,
        ),
        PATCH: bind(
            LockPatchController::class,
            DecryptMiddleware::class,
        ),
        DELETE: bind(
            LockDeleteController::class,
            DecryptMiddleware::class,
        ),
    ),
    route(
        path: '/message',
        POST: bind(
            MessagePostController::class,
            VerifySignatureMiddleware::class
        ),
    ),
    route(
        path: '/dump',
        GET: DumpStreamController::class,
    ),
);
