<?php

namespace Whitecube\Sluggable;

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

        if ($this->attributeIsTranslatable($key)) {
            return $this->where($key . '->' . app()->getLocale(), $value)->first();
        } else {
            return $this->where($key, $value)->first();
        }
    }
}
