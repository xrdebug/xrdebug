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

namespace Chevere\xrDebug\Constants;

/**
 * Defines the regex used in the URL path.
 */
enum UrlPathRegex
{
    /**
     * @var string Regex used to match an integer id.
     */
    public const ID = '/^\d+$/';

    /**
     * @var string Regex used to match a UUID.
     */
    public const UUID = '/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';
}
