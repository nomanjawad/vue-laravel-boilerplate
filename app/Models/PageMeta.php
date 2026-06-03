<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMeta extends Model
{
    protected $fillable = ['route_name', 'meta_title', 'meta_description', 'og_image'];
}
