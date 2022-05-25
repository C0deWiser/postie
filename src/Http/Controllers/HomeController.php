<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, PostieAssets $assets, Postie $postie)
    {
        $possibleNotifications = $postie->getUserNotifications($request->user());

        if (!$possibleNotifications) {
            abort(404);
        }

        return view('postie::layout', [
            'assetsAreCurrent' => $assets->assetsAreCurrent(),
            'cssFile' => 'app.css',
            'cssBootstrapIcons' => 'bootstrap-icons.css',
            'postieScriptVariables' => $assets->scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
