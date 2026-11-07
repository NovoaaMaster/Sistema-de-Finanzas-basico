<?php

namespace App\Pclap;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientePclap extends ModeloPclap
{
    protected $table = 'clientes';

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PersonaPclap::class, 'persona_id');
    }
}
