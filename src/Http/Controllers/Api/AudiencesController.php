<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;

class AudiencesController extends Controller
{
    /**
     * Get all defined audiences.
     */
    public function index(Request $request, PostieService $postie)
    {
        return response()->json([
            'data' => $postie->getAudiences()->values()->toArray(),
        ]);
    }

    /**
     * Get a defined audience by its name.
     */
    public function show(Request $request, PostieService $postie, string $audience)
    {
        $audience = $postie->getAudiences()->first(
            fn(Audience $definition) => $definition->getName() === $audience
        );

        abort_unless($audience !== null, 404);

        return response()->json([
            'data' => $audience->toArray(),
        ]);
    }
}