<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     */
    public function index(Request $request, PostieService $postie)
    {
        $groups = $postie->getGroups($request->user());

        return view('postie::layout', [
            'assetsAreCurrent'      => $postie->assetsAreCurrent(),
            'cssFile'               => 'app.css',
            'cssBootstrapIcons'     => 'bootstrap-icons.css',
            'postieScriptVariables' => $postie->scriptVariables(),
            'isDownForMaintenance'  => app()->isDownForMaintenance(),
            'groups'                => $groups->reorder(),
            'trans'                 => Arr::dot([
                'subscriptions' => Lang::get('postie::subscriptions')
            ])
        ]);
    }
}
