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

use Chevere\XrDebug\Controllers\MessagePostController;
use Chevere\XrDebug\Controllers\PauseDeleteController;
use Chevere\XrDebug\Controllers\PauseGetController;
use Chevere\XrDebug\Controllers\PausePatchController;
use Chevere\XrDebug\Controllers\PausePostController;
use Chevere\XrDebug\Controllers\SPAController;
use Chevere\XrDebug\Controllers\StreamController;
use Chevere\XrDebug\Middlewares\DecryptMiddleware;
use Chevere\XrDebug\Middlewares\VerifySignatureMiddleware;
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
        path: '/pauses',
        POST: bind(
            PausePostController::class,
            VerifySignatureMiddleware::class,
        ),
    ),
    route(
        path: '/pauses/{id}',
        GET: bind(
            PauseGetController::class,
            VerifySignatureMiddleware::class,
        ),
        PATCH: bind(
            PausePatchController::class,
            DecryptMiddleware::class,
        ),
        DELETE: bind(
            PauseDeleteController::class,
            DecryptMiddleware::class,
        ),
    ),
    route(
        path: '/messages',
        POST: bind(
            MessagePostController::class,
            VerifySignatureMiddleware::class
        ),
    ),
    route(
        path: '/stream',
        GET: StreamController::class,
    ),
);
