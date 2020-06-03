<?php

namespace Whitecube\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
use Whitecube\Sluggable\HasSlug;

class TestModelTranslated extends Model
{
    use SoftDeletes;
    use HasSlug;
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
