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

namespace Chevere\XrServer\Middleware;

use phpseclib3\Crypt\AES;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Chevere\Writer\streamTemp;
use function Chevere\XrServer\decrypt;

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
        $request = $request->withBody(
            streamTemp(
                decrypt($this->cipher, $request->getBody()->__toString())
            )
        );

        return $handler->handle($request);
    }
}
