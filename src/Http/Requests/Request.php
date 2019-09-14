<?php

namespace Laralord\Orion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws \Exception
     */
    public function authorize()
    {
        throw new \Exception('Authorization logic is not defined for '.static::class.' request class.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->isMethod('POST')) {
            return array_merge($this->commonRules(), $this->storeRules());
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return array_merge($this->commonRules(), $this->updateRules());
        }

        return $this->commonRules();
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
