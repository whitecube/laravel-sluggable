<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Whitecube\Sluggable\HasSlug;

class TestModelCustomAttribute extends Model
{
    use HasSlug;

    protected $table = 'test_model_custom_attributes';

    protected $guarded = [];

    public $timestamps = false;

    public $sluggable = 'title';
    
    public $slugStorageAttribute = 'url';
}
