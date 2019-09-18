<?php

namespace Laralord\Orion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // authorization is handled in controllers
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->route()->getActionMethod() === 'store') {
            return array_merge($this->commonRules(), $this->storeRules());
        }

        if ($this->route()->getActionMethod() === 'update') {
            return array_merge($this->commonRules(), $this->updateRules());
        }

        return [];
    }

    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules()
    {
        return [];
    }

    /**
     * Rules for the "store" (POST) method.
     *
     * @return array
     */
    public function storeRules()
    {
        return [];
    }

    /**
     * Rules for the "update" (PATCH|PUT) method.
     *
     * @return array
     */
    public function updateRules()
    {
        return [];
    }
}
