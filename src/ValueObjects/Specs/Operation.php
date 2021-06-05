<?php

declare(strict_types=1);

namespace Orion\ValueObjects\Specs;

use Illuminate\Contracts\Support\Arrayable;

class Operation implements Arrayable
{
    /** @var string */
    public $id;
    /** @var string */
    public $method;
    /** @var array */
    public $parameters;
    /** @var string */
    public $summary;
    /** @var Request|null */
    public $request;
    /** @var Response[] */
    public $responses;
    /** @var string[] */
    public $tags;

    public function toArray(): array
    {
        $operation = [
            'parameters' => $this->parameters,
            'summary' => $this->summary,
            'responses' => collect($this->responses)->mapWithKeys(
                function (Response $response) {
                    return [(string)$response->statusCode => $response->toArray()];
                }
            )->toArray(),
            'tags' => $this->tags
        ];

        if ($this->request) {
            $operation['requestBody'] = $this->request->toArray();
        }

        ksort($operation);

        return $operation;
    }
}
