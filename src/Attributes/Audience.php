<?php

namespace Codewiser\Postie\Attributes;

use Codewiser\Postie\Audience as AudienceDefinition;
use Codewiser\Postie\PostieService;
use function app;

/**
 * Scope notification for given audience.
 *
 * The audience is referenced by a name of a predefined Audience definition
 * (see PostieService::$audiences).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Audience
{
    public function __construct(public string|\BackedEnum $name)
    {
        //
    }

    public function toAudience(): AudienceDefinition
    {
        return app(PostieService::class)
            ->findAudience($this->name);
    }
}