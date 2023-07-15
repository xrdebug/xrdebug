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

namespace Chevere\Tests;

use Chevere\XrServer\Build;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\directoryForPath;

final class XrBuildTest extends TestCase
{
    public function testConstruct(): void
    {
        $build = new Build(
            directoryForPath(__DIR__ . '/src/app/src'),
            '6.6.6',
            'ElNúmeroDeLaBestia'
        );
        $this->assertStringEqualsFile(
            __DIR__ . '/src/app/build/index.html',
            $build->html()
        );
    }
}
