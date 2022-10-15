<?php

namespace Orion\Helper;

use Illuminate\Support\Facades\App;
use Orion\Http\Requests\Request;

class RequestHelper extends Helper
{
    // For some reason when the version is older, query params are included in $request->post()
    public static function getPostRequestParam($key = null, $default = null) {
        if ((float) app()->version() < 9.0) {
            return $key ? $_POST[$key] ?? $default : $_POST;
        }
        return App::make(Request::class)->post($key, $default);
    }
}
