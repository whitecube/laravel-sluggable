<?php

namespace Whitecube\Sluggable;

use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\UrlGenerator;
use \Route;
use Illuminate\Contracts\Routing\UrlRoutable;

trait Sluggable
{
    /**
     * Register the saving event callback
     */
    public static function bootSluggable()
    {
        static::saving(function($model) {
            $model->attributes[$model->getSluggableAttribute()] = $model->generateSlug();
        });
    }

    public function getSluggableAttribute()
    {
        return $this->sluggableAttribute ?? 'slug';
    }

    /**
     * @param Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @return string
     */
    public function getModelUrl($model, $key)
    {
        $route = Route::current();

        foreach($route->signatureParameters(UrlRoutable::class) as $parameter) {
            if($parameter->getClass()->name !== get_class()) continue;
            break;
        }


        $route->setParameter($parameter->name, $model->$key);

        return app(UrlGenerator::class)->toRoute($route, $route->parameters(), false);
    }

    /**
     * Get the column to generate the slug from.
     *
     * @return string
     */
    abstract public function sluggable(): string;

    /**
     * Generate the slug.
     *
     * @return false|string
     */
    protected function generateSlug()
    {
        return $this->slugify($this->sluggable());
    }

    /**
     * Handle the slug generation for translatable and
     * non-translatable attributes.
     *
     * @param string $attribute
     * @return false|string
     */
    protected function slugify($attribute)
    {
        if($this->attributeIsTranslatable($attribute)) {
            return json_encode($this->translatedSlugs($attribute));
        }

        return str_slug($this->$attribute);
    }

    /**
     * Handle the generation of translated slugs
     *
     * @param string $attribute
     * @return array
     */
    protected function translatedSlugs($attribute)
    {
        $slugs = [];

        foreach($this->getTranslatedLocales($attribute) as $locale) {
            $slugs[$locale] = str_slug($this->getTranslation($attribute, $locale));
        }

        return $slugs;
    }

    /**
     * Check if the model has the HasTranslations trait.
     *
     * @return bool
     */
    protected function hasTranslatableTrait()
    {
        return method_exists($this, 'getTranslations');
    }

    /**
     * Check if the given attribute is translatable.
     *
     * @param string $attribute
     * @return bool
     */
    protected function attributeIsTranslatable($attribute)
    {
        return $this->hasTranslatableTrait() && $this->isTranslatableAttribute($attribute);
    }

    /**
     * Override the route key name to allow proper
     * Route-Model binding.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getSluggableAttribute();
    }

    /**
     * Resolve the route binding with the translatable slug in mind
     *
     * @param $value
     * @return mixed
     */
    public function resolveRouteBinding($value)
    {
        $key = $this->getRouteKeyName();

        if(!$this->attributeIsTranslatable($key)) {
            return parent::resolveRouteBinding($value);
        }

        // Return exact match if we find it
        if($result = $this->where($key . '->' . app()->getLocale(), $value)->first()) {
            return $result;
        }

        // If cross-lang redirects are disabled, stop here
        if($this->disableCrossLangRedirect) {
            return;
        }

        // Get the models where this slug exists in other langs as well
        $results = $this->whereRaw('JSON_SEARCH(`'.$key.'`, "one", "'.$value.'")')->get();

        // If we have zero or multiple results, don't guess
        if($results->count() !== 1) {
            return;
        }

        // Redirect to the current route using the translated model key
        return abort(301, '', ['Location' => $this->getModelUrl($results->first(), $key)]);
    }
}
