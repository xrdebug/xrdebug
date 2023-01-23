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

use Chevere\Controller\HttpController;

class SinglePageApp extends HttpController
{
    /**
     * @return array<string>
     */
    public function run(): array
    {
        return [];
    }
}
