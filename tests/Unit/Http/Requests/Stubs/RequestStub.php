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
    public function commonRules() : array
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
    public function storeRules() : array
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
    public function updateRules() : array
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
    public function associateRules() : array
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
    public function attachRules() : array
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
    public function detachRules() : array
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
    public function syncRules() : array
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
    public function toggleRules() : array
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
    public function updatePivotRules() : array
    {
        return [
            'update-pivot-rules-field' => 'required'
        ];
    }
}
