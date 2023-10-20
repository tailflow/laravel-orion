<?php

declare(strict_types=1);

namespace Orion\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orion\Tests\Fixtures\App\Traits\AppliesDefaultOrder;

class Team extends Model
{
    use AppliesDefaultOrder;

    protected $fillable = [
        'name', 'description'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
