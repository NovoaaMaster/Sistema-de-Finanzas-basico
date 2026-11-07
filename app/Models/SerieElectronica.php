<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerieElectronica extends Model
{
    protected $table = 'series_electronicas';

    protected $guarded = ['id'];

    protected $casts = [
        'correlativo' => 'integer',
    ];
}
