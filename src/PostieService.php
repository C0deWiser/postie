<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Contracts\PostieAssets;
use Illuminate\Support\Facades\File;

class PostieService implements PostieAssets
{
    public static array $notifications = [];

    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/postie/mix-manifest.json');

        if (!File::exists($publishedPath)) {
            throw new \RuntimeException('Postie assets are not published. Please run: php artisan postie:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__ . '/../public/mix-manifest.json');
    }

    public function scriptVariables(): array
    {
        return [
            'path' => config('postie.path'),
        ];
    }
}
