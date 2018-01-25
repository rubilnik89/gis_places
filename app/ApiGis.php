<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiGis extends Model
{
    protected $fillable = [
        'data', 'url', 'descr'
    ];
}
