<?php

namespace Orion\Http\Requests;

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
        if (!$this->route()) {
            return [];
        }

        if ($this->route()->getActionMethod() === 'store') {
            return array_merge($this->commonRules(), $this->storeRules());
        }

        if ($this->route()->getActionMethod() === 'batchStore') {
            return $this->buildBatchRules($this->storeRules(), $this->batchStoreRules());
        }

        if ($this->route()->getActionMethod() === 'update') {
            return array_merge($this->commonRules(), $this->updateRules());
        }

        if ($this->route()->getActionMethod() === 'batchUpdate') {
            return $this->buildBatchRules($this->updateRules(), $this->batchUpdateRules());
        }

        if ($this->route()->getActionMethod() === 'associate') {
            return array_merge(
                [
                    'related_key' => 'required',
                ],
                $this->associateRules()
            );
        }

        if ($this->route()->getActionMethod() === 'attach') {
            return array_merge(
                [
                    'resources' => 'present',
                    'duplicates' => ['sometimes', 'boolean'],
                ],
                $this->attachRules()
            );
        }

        if ($this->route()->getActionMethod() === 'detach') {
            return array_merge(
                [
                    'resources' => 'present',
                ],
                $this->detachRules()
            );
        }

        if ($this->route()->getActionMethod() === 'sync') {
            return array_merge(
                [
                    'resources' => 'present',
                    'detaching' => ['sometimes', 'boolean'],
                ],
                $this->syncRules()
            );
        }

        if ($this->route()->getActionMethod() === 'toggle') {
            return array_merge(
                [
                    'resources' => 'present',
                ],
                $this->toggleRules()
            );
        }

        if ($this->route()->getActionMethod() === 'updatePivot') {
            return array_merge(
                [
                    'pivot' => ['required', 'array'],
                ],
                $this->updatePivotRules()
            );
        }

        return [];
    }

    /**
     * Get custom attributes for validator errors that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        if (!$this->route()) {
            return [];
        }

        if ($this->route()->getActionMethod() === 'store') {
            return array_merge($this->commonMessages(), $this->storeMessages());
        }

        if ($this->route()->getActionMethod() === 'batchStore') {
            return array_merge($this->commonMessages(), $this->storeMessages(), $this->batchStoreMessages());
        }

        if ($this->route()->getActionMethod() === 'update') {
            return array_merge($this->commonMessages(), $this->updateMessages());
        }

        if ($this->route()->getActionMethod() === 'batchUpdate') {
            return array_merge($this->commonMessages(), $this->updateMessages(), $this->batchUpdateMessages());
        }

        if ($this->route()->getActionMethod() === 'associate') {
            return $this->associateMessages();
        }

        if ($this->route()->getActionMethod() === 'attach') {
            return $this->attachMessages();
        }

        if ($this->route()->getActionMethod() === 'detach') {
            return $this->detachMessages();
        }

        if ($this->route()->getActionMethod() === 'sync') {
            return $this->syncMessages();
        }

        if ($this->route()->getActionMethod() === 'toggle') {
            return $this->toggleMessages();
        }

        if ($this->route()->getActionMethod() === 'updatePivot') {
            return $this->updatePivotMessages();
        }

        return [];
    }

    /**
     * Default rules for the request.
     *
     * @return array
     */
    public function commonRules(): array
    {
        return [];
    }

    /**
     * Rules for the "store" (POST) endpoint.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [];
    }

    protected function buildBatchRules($definedRules, $definedBatchRules): array
    {
        $batchRules = [
            'resources' => ['array', 'required'],
        ];

        $mergedRules = array_merge($this->commonRules(), $definedRules, $definedBatchRules);

        foreach ($mergedRules as $ruleField => $fieldRules) {
            $batchRules["resources.*.{$ruleField}"] = $fieldRules;
        }

        return $batchRules;
    }

    /**
     * Rules for the "batch store" (POST) endpoint.
     *
     * @return array
     */
    public function batchStoreRules(): array
    {
        return [];
    }

    /**
     * Rules for the "update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [];
    }

    /**
     * Rules for the "batch update" (PATCH|PUT) endpoint.
     *
     * @return array
     */
    public function batchUpdateRules(): array
    {
        return [];
    }

    /**
     * Rules for the "associate" endpoint.
     *
     * @return array
     */
    public function associateRules(): array
    {
        return [];
    }

    /**
     * Rules for the "attach" endpoint.
     *
     * @return array
     */
    public function attachRules(): array
    {
        return [];
    }

    /**
     * Rules for the "detach" endpoint.
     *
     * @return array
     */
    public function detachRules(): array
    {
        return [];
    }

    /**
     * Rules for the "sync" endpoint.
     *
     * @return array
     */
    public function syncRules(): array
    {
        return [];
    }

    /**
     * Rules for the "toggle" endpoint.
     *
     * @return array
     */
    public function toggleRules(): array
    {
        return [];
    }

    /**
     * Rules for the "pivot" endpoint.
     *
     * @return array
     */
    public function updatePivotRules(): array
    {
        return [];
    }

    /**
     * Default messages for the request.
     *
     * @return array
     */
    public function commonMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "store" (POST) endpoint.
     *
     * @return array
     */
    function storeMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "batchstore" (POST) endpoint.
     *
     * @return array
     */
    function batchStoreMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "update" (POST) endpoint.
     *
     * @return array
     */
    function updateMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "batchUpdate" (POST) endpoint.
     *
     * @return array
     */
    function batchUpdateMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "associate" endpoint.
     *
     * @return array
     */
    public function associateMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "attach" endpoint.
     *
     * @return array
     */
    public function attachMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "detach" endpoint.
     *
     * @return array
     */
    public function detachMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "sync" endpoint.
     *
     * @return array
     */
    public function syncMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "toggle" endpoint.
     *
     * @return array
     */
    public function toggleMessages(): array
    {
        return [];
    }

    /**
     * Messages for the "pivot" endpoint.
     *
     * @return array
     */
    public function updatePivotMessages(): array
    {
        return [];
    }
}
