<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\Http\Requests\Api\SubscriptionChannelRequest;
use Codewiser\Postie\PostieService;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class PreferencesController extends Controller
{
    /**
     * Subscribe user to a channel of the notification.
     */
    public function update(SubscriptionChannelRequest $request, PostieService $postie)
    {
        $this->find($postie, $request->user(), $request->notification);

        $postie->toggleUserPreferences(
            $request->user(),
            $request->notification,
            [$request->channel => true]
        );

        return response()->json([
            'data' => $this->payload($postie, $request->user(), $request->notification),
        ]);
    }

    /**
     * Unsubscribe user from a channel of the notification.
     */
    public function destroy(SubscriptionChannelRequest $request, PostieService $postie)
    {
        $this->find($postie, $request->user(), $request->notification);

        $postie->toggleUserPreferences(
            $request->user(),
            $request->notification,
            [$request->channel => false]
        );

        return response()->json([
            'data' => $this->payload($postie, $request->user(), $request->notification),
        ]);
    }

    /**
     * Build subscription payload with user preferences applied.
     */
    protected function payload(PostieService $postie, $user, string $notification): array
    {
        $subscription = $this->find($postie, $user, $notification);

        return (new Subscriptions([$subscription]))->withNotifiable($user)[0];
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