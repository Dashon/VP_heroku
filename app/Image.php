<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'school_Id', 'category', 'description', 'order'
        ];
}
