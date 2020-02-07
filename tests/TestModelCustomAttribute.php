<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Whitecube\Sluggable\Sluggable;

class TestModelCustomAttribute extends Model
{
    use Sluggable;

    protected $table = 'test_model_custom_attributes';

    protected $guarded = [];

    public $timestamps = false;

    public $sluggable = 'title';
    
    public $slugColumn = 'url';
}
