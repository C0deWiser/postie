<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class SubscriptionsController extends Controller
{
    /**
     * Get all subscriptions with channels for the current user.
     *
     * Optionally filter by a group shortcode (`?group={shortcode}`).
     */
    public function index(Request $request, PostieService $postie)
    {
        $subscriptions = $postie->getSubscriptions($request->user())
            ->when($request->input('group'),
                // Filter by requested group
                fn(Subscriptions $subscriptions, string $shortcode) => $subscriptions->filterByGroup($shortcode)
            );

        return response()->json([
            'data' => $subscriptions->withNotifiable($request->user()),
        ]);
    }

    /**
     * Get a single subscription with channels for the current user.
     */
    public function show(Request $request, PostieService $postie, string $notification)
    {
        $subscription = $this->find($postie, $request->user(), $notification);

        return response()->json([
            'data' => (new Subscriptions([$subscription]))->withNotifiable($request->user())[0],
        ]);
    }

    /**
     * Find a subscription the user may manage.
     */
    protected function find(PostieService $postie, $user, string $notification)
    {
        try {
            return $postie->getSubscriptions($user)->find($notification);
        } catch (ItemNotFoundException|MultipleItemsFoundException $e) {
            abort(404);
        }
    }
}