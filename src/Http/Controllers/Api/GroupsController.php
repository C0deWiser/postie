<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Group;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    /**
     * Get groups of subscriptions for the current user.
     */
    public function index(Request $request, PostieService $postie)
    {
        return response()->json([
            'data' => $postie->getGroups($request->user())->reorder()->values()->toArray(),
        ]);
    }

    /**
     * Get a group by its shortcode.
     */
    public function show(Request $request, PostieService $postie, string $group)
    {
        $group = $postie->getGroups($request->user())
            ->first(fn(Group $definition) => $definition->getShortcode() === $group);

        abort_unless($group !== null, 404);

        return response()->json([
            'data' => $group->toArray(),
        ]);
    }
}