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

use Chevere\Tests\src\Traits\CipherTrait;
use PHPUnit\Framework\TestCase;
use function Chevere\XrServer\decrypt;
use function Chevere\XrServer\encrypt;
use function Chevere\XrServer\getDump;

final class FunctionsTest extends TestCase
{
    use CipherTrait;

    public function testEncryptDecrypt(): void
    {
        $symmetricKey = $this->getCipher();
        $message = "I'm the miggida miggida miggida miggida Mac Daddy";
        $encrypt = encrypt($symmetricKey, $message);
        $decrypt = decrypt($symmetricKey, $encrypt);
        $this->assertSame($message, $decrypt);
    }

    public function dataProvider(): array
    {
        return [
            [
                [],
                [
                    'message' => '',
                    'file_path' => '<file>',
                    'file_line' => '<line>',
                    'file_display' => '<file>:<line>',
                    'file_display_short' => '<file>:<line>',
                    'emote' => '',
                    'topic' => '',
                    'id' => '',
                    'action' => 'action',
                ],
            ],
            [
                [
                    'body' => '<script>alert("xss")</script>',
                    'file_path' => '',
                    'file_line' => '',
                ],
                [
                    'message' => '',
                    'file_path' => '',
                    'file_line' => '',
                    'file_display' => '',
                    'file_display_short' => '',
                    'emote' => '',
                    'topic' => '',
                    'id' => '',
                    'action' => 'action',
                ],
            ],
            [
                [
                    'id' => 'id',
                    'body' => 'body',
                    'file_path' => 'file_path',
                    'file_line' => 'file_line',
                    'emote' => 'emote',
                    'topic' => 'topic',
                ],
                [
                    'message' => 'body',
                    'file_path' => 'file_path',
                    'file_line' => 'file_line',
                    'file_display' => 'file_path:file_line',
                    'file_display_short' => 'file_path:file_line',
                    'emote' => 'emote',
                    'topic' => 'topic',
                    'id' => 'id',
                    'action' => 'action',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetDump(array $body, array $expected): void
    {
        $dump = getDump($body, 'action');
        $this->assertSame($expected, $dump->toArray());
    }
}
