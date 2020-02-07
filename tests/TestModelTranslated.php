<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Whitecube\Sluggable\Sluggable;

class TestModelTranslated extends Model
{
    use Sluggable;
    use HasTranslations;

    protected $table = 'test_model_translatables';

    protected $guarded = [];

    public $timestamps = false;

    public $translatable = [
        'title', 'name', 'slug'
    ];

    public function getSluggable()
    {
        return 'title';
    }
}
