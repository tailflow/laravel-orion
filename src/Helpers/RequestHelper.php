<?php

namespace Orion\Helpers;

use Illuminate\Support\Facades\App;
use Orion\Http\Requests\Request;

class RequestHelper extends Helper
{
    // For some reason when the version is older, query params are included in $request->post()
    // @TODO: Remove for version >= Laravel 9.0
    public static function getPostRequestParam($key = null, $default = null) {
        $request = App::make(Request::class);
        if ($request->query() === $request->post()) {
            return $key ? $_POST[$key] ?? $default : $_POST;
        }
        return $request->post($key, $default);
    }
}
