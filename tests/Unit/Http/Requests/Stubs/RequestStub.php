<?php

namespace Orion\Tests\Unit\Http\Requests\Stubs;

use Orion\Http\Requests\Request;

class RequestStub extends Request
{
    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules()
    {
        return [
            'common-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "store" (POST) endpoint.
     *
     * @return array
     */
    public function storeRules()
    {
        return [
            'store-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function updateRules()
    {
        return [
            'update-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "associate" endpoint.
     *
     * @return array
     */
    public function associateRules()
    {
        return [
            'associate-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "attach" endpoint.
     *
     * @return array
     */
    public function attachRules()
    {
        return [
            'attach-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "detach" endpoint.
     *
     * @return array
     */
    public function detachRules()
    {
        return [
            'detach-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "sync" endpoint.
     *
     * @return array
     */
    public function syncRules()
    {
        return [
            'sync-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "toggle" endpoint.
     *
     * @return array
     */
    public function toggleRules()
    {
        return [
            'toggle-rules-field' => 'required'
        ];
    }

    /**
     * Rules for the "pivot" endpoint.
     *
     * @return array
     */
    public function updatePivotRules()
    {
        return [
            'update-pivot-rules-field' => 'required'
        ];
    }
}
