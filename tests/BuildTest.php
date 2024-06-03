<?php

/*
 * This file is part of xrDebug.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Chevere\Tests;

use Chevere\Filesystem\Exceptions\DirectoryNotExistsException;
use Chevere\Filesystem\File;
use Chevere\VarDump\Outputs\HtmlOutput;
use Chevere\xrDebug\Build;
use PHPUnit\Framework\TestCase;
use function Chevere\Filesystem\directoryForPath;

final class BuildTest extends TestCase
{
    public function testNonExistentDirectory(): void
    {
        $directory = directoryForPath(__DIR__ . '/non-existent');
        $this->expectException(DirectoryNotExistsException::class);
        new Build($directory, 'version');
    }

    public function testConstruct(): void
    {
        $source = directoryForPath(__DIR__ . '/build-mock/source');
        $build = new Build(
            $source,
            '6.6.6',
        );
        $filePath = $source->path()->getChild('var-dump.css');
        $file = new File($filePath);
        $file->removeIfExists();
        $file->create();
        $file->put(HtmlOutput::CSS);
        $this->assertStringEqualsFile(
            __DIR__ . '/build-mock/output/index.html',
            $build->__toString()
        );
    }
}
