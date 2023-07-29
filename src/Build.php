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

namespace Chevere\xrDebug;

use Chevere\Filesystem\File;
use Chevere\Filesystem\Interfaces\DirectoryInterface;
use Stringable;

final class Build implements Stringable
{
    private string $string;

    public function __construct(
        private DirectoryInterface $source,
        private string $version,
        private string $codename,
        private string $sessionName = 'xrDebug',
        private string $editor = 'vscode',
        bool $isEncryptionEnabled = false,
        bool $isSignVerificationEnabled = false
    ) {
        $source->assertExists();
        $file = new File($source->path()->getChild('index.html'));
        $this->string = $file->getContents();
        $this->replace('%version%', $this->version);
        $this->replace('%codename%', $this->codename);
        $this->replace('%isEncryptionEnabled%', $isEncryptionEnabled ? 'true' : 'false');
        $this->replace('%nonceLength%', strval(cipherNonceLength()));
        $this->replace('%tagLength%', strval(cipherTagLength()));
        $this->replace('%sessionName%', $this->sessionName);
        $this->replace('%editor%', $this->editor);
        $security = match (true) {
            $isEncryptionEnabled && $isSignVerificationEnabled => 'End-to-end encrypted and sign verified',
            $isEncryptionEnabled => 'End-to-end encrypted',
            $isSignVerificationEnabled => 'Sign verified',
            default => '',
        };
        $this->replace('%security%', $security);
        $this->replaceIcons('svg', 'image/svg+xml');
        $this->replaceIcons('png', 'image/png');
        $this->replaceStyles();
        $this->replaceFont('fonts/firacode/firacode-regular.woff', 'font/woff');
        $this->replaceScripts();
    }

    public function __toString(): string
    {
        return $this->string;
    }

    private function replaceStyles(): void
    {
        preg_match_all(
            '#<link rel="stylesheet".*(href=\"(.*)\")>#',
            $this->string,
            $files
        );
        foreach ($files[0] as $pos => $match) {
            $fileMatch = new File($this->source->path()->getChild($files[2][$pos]));
            $replace = '<style media="all">' . $fileMatch->getContents() . '</style>';
            $this->replace($match, $replace);
        }
    }

    private function replaceScripts(): void
    {
        preg_match_all("#<script .*(src=\"(.*)\")><\/script>#", $this->string, $files);
        foreach ($files[0] as $pos => $match) {
            $fileMatch = new File($this->source->path()->getChild($files[2][$pos]));
            /** @var string $replace */
            $replace = str_replace(' ' . $files[1][$pos], '', $match);
            $replace = str_replace(
                '></script>',
                '>'
                    . $fileMatch->getContents()
                    . '</script>',
                $replace
            );
            $this->replace($match, $replace);
        }
    }

    private function replaceIcons(string $extension, string $mime): void
    {
        preg_match_all(
            '#="(icon\.' . $extension . ')"#',
            $this->string,
            $files
        );
        foreach ($files[0] as $pos => $match) {
            $fileMatch = new File($this->source->path()->getChild($files[1][$pos]));
            $replace = '="data:' . $mime . ';base64,'
                . base64_encode($fileMatch->getContents())
                . '"';
            $this->replace($match, $replace);
        }
    }

    private function replaceFont(string $font, string $mime): void
    {
        $fileMatch = new File($this->source->path()->getChild($font));
        $replace = 'url(data:' . $mime . ';base64,'
                . base64_encode($fileMatch->getContents())
                . ')';
        $this->replace(
            "url('{$font}')",
            $replace
        );
    }

    private function replace(string $search, string $replace): void
    {
        $this->string = str_replace($search, $replace, $this->string);
    }
}
