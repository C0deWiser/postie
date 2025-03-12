<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Contracts\Postie;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class PreviewingController extends Controller
{
    public function __invoke(Request $request, Postie $postie)
    {
        $channelName = $request->route('channel');
        $notification = $request->route('notification');

        try {
            $definition = $postie->getNotifications()->find($notification);
        } catch (ItemNotFoundException|MultipleItemsFoundException $exception) {
            abort(404, "Notification {$notification} Not Found");
        }

        try {
            $definition->getChannels()->find($channelName);
        } catch (ItemNotFoundException|MultipleItemsFoundException $exception) {
            abort(404, "Channel {$channelName} Not Found");
        }

        $previewing = $definition
            ->getPreview($channelName, $request->user());

        if (!$previewing) {
            abort(404, "{$notification} has no preview configured");
        }

        if ($previewing instanceof Renderable) {
            return $previewing->render($request);
        }

        return $previewing;
    }
}
