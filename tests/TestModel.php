<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Whitecube\Sluggable\Sluggable;

class TestModel extends Model
{
    use Sluggable;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public $sluggable = 'title';
}
