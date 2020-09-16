<?php

namespace Orion\Tests\Fixtures\App\Http\Requests;

use Orion\Http\Requests\Request;

class PostRequest extends Request
{
    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules() : array
    {
        return [];
    }

    /**
     * Rules for the "store" (POST) endpoint.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            'title' => ['string', 'required', 'max:255'],
            'body' => ['string', 'required']
        ];
    }

    /**
     * Rules for the "update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function updateRules() : array
    {
        return [
            'title' => ['string', 'required', 'max:255']
        ];
    }
}
