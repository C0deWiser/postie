<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\PostieService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PreviewingController extends Controller
{
    public function __invoke(Request $request, PostieService $postie)
    {
        $channel = $request->route('channel');
        $notification = $request->route('notification');

        try {
            $subscription = $postie->getSubscriptions()->find($notification);
        } catch (ItemNotFoundException|MultipleItemsFoundException) {
            throw new NotFoundHttpException("Notification $notification Not Found");
        }

        try {
            $subscription->getChannels()->find($channel);
        } catch (ItemNotFoundException|MultipleItemsFoundException) {
            throw new NotFoundHttpException("Channel $channel Not Found");
        }

        $preview = $subscription->getPreview($channel, $request->user());

        if (! $preview) {
            throw new NotFoundHttpException("$notification has no preview configured");
        }

        if ($preview instanceof Renderable) {
            return $preview->render($request);
        }

        return $preview;
    }
}
