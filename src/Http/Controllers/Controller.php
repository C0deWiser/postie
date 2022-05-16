<?php

namespace Codewiser\Postie\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }
}
