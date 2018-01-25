<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GisPlace extends Model
{
    protected $fillable = [
        'lat', 'lon', 'id', 'name', 'type', 'options', 'photos', 'city', 'photo_uploaded'
    ];
}
