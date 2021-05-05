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
        return [
            'operationId' => $this->id,
            'summary' => $this->summary,
            'requestBody' => $this->request ? $this->request->toArray() : null,
            'responses' => collect($this->responses)->mapWithKeys(
                function (Response $response) {
                    return [$response->statusCode => $response->toArray()];
                }
            )->toArray(),
            'tags' => $this->tags
        ];
    }
}
