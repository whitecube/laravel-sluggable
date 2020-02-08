<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Whitecube\Sluggable\HasSlug;

class TestModel extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public $sluggable = 'title';
}
