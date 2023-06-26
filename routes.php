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

use Chevere\XrServer\Controller\DumpStreamController;
use Chevere\XrServer\Controller\LockDeleteController;
use Chevere\XrServer\Controller\LockGetController;
use Chevere\XrServer\Controller\LockPatchController;
use Chevere\XrServer\Controller\LockPostController;
use Chevere\XrServer\Controller\MessageDumpController;
use Chevere\XrServer\Controller\SPAController;
use Chevere\XrServer\Middleware\DecryptMiddleware;
use Chevere\XrServer\Middleware\VerifySignatureMiddleware;
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
            MessageDumpController::class,
            VerifySignatureMiddleware::class
        ),
    ),
    route(
        path: '/dump',
        GET: DumpStreamController::class,
    ),
);
