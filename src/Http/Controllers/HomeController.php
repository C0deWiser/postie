<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     */
    public function index(Request $request, PostieService $postie)
    {
        $subscriptions = $postie->getUserNotifications($request->user());

        if (!$subscriptions) {
            abort(404);
        }

        $groups = collect($subscriptions)
            // Extract groups from subscriptions
            ->map(function ($subscription) {
                return $subscription['group'];
            })
            ->unique()
            ->values();

        return view('postie::layout', [
            'assetsAreCurrent' => $postie->assetsAreCurrent(),
            'cssFile' => 'app.css',
            'cssBootstrapIcons' => 'bootstrap-icons.css',
            'postieScriptVariables' => $postie->scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
            'groups' => $groups,
            'trans' => Arr::dot([
                'subscriptions' => Lang::get('postie::subscriptions')
            ])
        ]);
    }
}
