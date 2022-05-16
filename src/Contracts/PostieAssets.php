<?php

namespace Codewiser\Postie\Contracts;

use RuntimeException;

interface PostieAssets
{
    /**
     * Determine if Postie's published assets are up-to-date.
     *
     * @throws RuntimeException
     */
    public function assetsAreCurrent(): bool;

    /**
     * Get the default JavaScript variables for Postie
     */
    public function scriptVariables(): array;
}
