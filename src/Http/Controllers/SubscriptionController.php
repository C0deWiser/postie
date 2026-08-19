<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Http\Requests\SubscriptionToggleRequest;
use Codewiser\Postie\Http\Resources\PreferenceResource;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * User notifications list.
     */
    public function index(Request $request, PostieService $postie)
    {
        $subscriptions = $postie->getSubscriptions($request->user())
            ->when($request->input('group'),
                // Filter by requested group
                fn(Subscriptions $subscriptions, string $shortcode) => $subscriptions->filterByGroup($shortcode)
            )
            ->withNotifiable($request->user());

        return response()->json([
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Change user preferences.
     */
    public function toggle(SubscriptionToggleRequest $request, PostieService $postie)
    {
        $preference = $postie->toggleUserPreferences(
            $request->user(),
            $request->notification,
            $request->channels,
            $request->variety
        );

        return PreferenceResource::make($preference);
    }
}
