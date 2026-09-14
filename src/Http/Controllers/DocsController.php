<?php

namespace Codewiser\Postie\Http\Controllers;

use Illuminate\Http\Response;

class DocsController extends Controller
{
    /**
     * Render the interactive OpenAPI documentation.
     */
    public function index(): Response
    {
        return response()->view('postie::api', [
            'specUrl' => url(config('postie.path').'/api/openapi.yaml'),
        ]);
    }

    /**
     * Serve the OpenAPI specification.
     */
    public function openapi(): Response
    {
        return response(file_get_contents(POSTIE_PATH.'/openapi.yaml'))
            ->header('Content-Type', 'text/yaml; charset=UTF-8');
    }
}