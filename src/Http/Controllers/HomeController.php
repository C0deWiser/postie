<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\PostieService;
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
    public function index(Request $request, PostieService $postie)
    {
        $possibleNotifications = $postie->getUserNotifications($request->user());

        if (!$possibleNotifications) {
            abort(404);
        }

        return view('postie::layout', [
            'assetsAreCurrent' => $postie->assetsAreCurrent(),
            'cssFile' => 'app.css',
            'cssBootstrapIcons' => 'bootstrap-icons.css',
            'postieScriptVariables' => $postie->scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
