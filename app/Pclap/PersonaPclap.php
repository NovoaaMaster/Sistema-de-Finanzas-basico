<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaPclap extends ModeloPclap
{
    protected $table = 'personas';

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoPclap::class, 'documento_id');
    }
}
