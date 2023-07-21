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

namespace Chevere\XrServer\Middlewares;

use Chevere\Http\Exceptions\MiddlewareException;
use phpseclib3\Crypt\EC\PrivateKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Safe\base64_decode;

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
        $signature = $request->getHeader('X-Signature');
        if ($signature === []) {
            throw new MiddlewareException(
                message: 'Missing signature',
                code: 400
            );
        }
        $body = $request->getParsedBody() ?? [];
        $serialize = serialize($body);
        $signature = base64_decode($signature[0]);
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
