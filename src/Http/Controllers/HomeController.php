<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Contracts\PostieAssets;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(PostieAssets $assets)
    {
        // TODO: remove after development
        Artisan::call('postie:publish', [

        ]);


        return view('postie::layout', [
            'assetsAreCurrent' => $assets->assetsAreCurrent(),
            'cssFile' => 'app.css',
            'postieScriptVariables' => $assets->scriptVariables(),
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
