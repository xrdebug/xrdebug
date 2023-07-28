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

namespace Chevere\xrDebug\Middlewares;

use phpseclib3\Crypt\AES;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Chevere\Writer\streamTemp;
use function Chevere\xrDebug\decrypt;

final class DecryptMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?AES $cipher = null
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->cipher === null) {
            return $handler->handle($request);
        }
        $body = decrypt($this->cipher, (string) $request->getBody());
        $stream = streamTemp($body);
        $request = $request->withBody($stream);

        return $handler->handle($request);
    }
}
