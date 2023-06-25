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

use Chevere\Http\Exceptions\MiddlewareException;
use phpseclib3\Crypt\EC\PrivateKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class VerifySignatureMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?PrivateKey $privateKey = null
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->privateKey === null) {
            return $handler->handle($request);
        }
        $signatureHeader = $request->getHeader('X-Signature');
        if ($signatureHeader === []) {
            throw new MiddlewareException(
                message: 'Missing signature',
                code: 400
            );
        }
        $body = $request->getParsedBody() ?? [];
        $serialize = serialize($body);
        $signature = base64_decode($signatureHeader[0], true);
        $publicKey = $this->privateKey->getPublicKey();
        if (! $publicKey->verify($serialize, $signature)) {
            throw new MiddlewareException(
                message: 'Invalid signature',
                code: 400
            );
        }

        return $handler->handle($request);
    }
}
