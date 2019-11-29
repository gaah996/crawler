<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Crawler extends Model
{
    protected $fillable = ['url', 'crawled', 'scrapped'];
}
