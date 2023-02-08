<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Postie Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Postie will be accessible from. If this
    | setting is null, Postie will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('POSTIE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Postie Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Postie will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('POSTIE_PATH', 'postie'),

    /*
    |--------------------------------------------------------------------------
    | Postie Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Postie route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => [
        'web'
    ],

    /*
    |--------------------------------------------------------------------------
    | Postie Subscriptions Table
    |--------------------------------------------------------------------------
    |
    | This is the table where Postie stores user subscription preferences.
    |
    */

    'table' => env('POSTIE_TABLE', 'subscriptions'),

];
