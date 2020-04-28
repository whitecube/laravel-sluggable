<?php

namespace Whitecube\Sluggable\Tests;

class SluggableTest extends TestCase
{
    public function test_it_saves_slug_on_model_save()
    {
        $model = TestModel::create(['title' => 'My test title']);

        $this->assertSame('my-test-title', $model->slug);
    }

    public function test_it_does_not_overwrite_existing_slug()
    {
        $model = TestModel::create([
            'title' => 'My test title',
            'slug' => 'custom-slug'
        ]);

        $this->assertSame('custom-slug', $model->slug);
    }

    public function test_it_saves_translated_slugs()
    {
        $model = TestModelTranslated::create([
            'title' => [
                'en' => 'My test title',
                'fr' => 'Mon titre test'
            ]
        ]);

        $this->assertSame('{"en":"my-test-title","fr":"mon-titre-test"}', $model->getAttributes()['slug']);
        $this->assertSame('my-test-title', $model->slug);
        $this->assertSame('mon-titre-test', $model->translate('slug', 'fr'));
    }

    public function test_it_only_generates_missing_translated_slugs()
    {
        $model = TestModelTranslated::create([
            'title' => [
                'en' => 'My test title',
                'fr' => 'Mon titre test'
            ],
            'slug' => [
                'en' => null,
                'fr' => 'custom-french-slug'
            ]
        ]);

        $this->assertSame('{"en":"my-test-title","fr":"custom-french-slug"}', $model->getAttributes()['slug']);
        $this->assertSame('my-test-title', $model->slug);
        $this->assertSame('custom-french-slug', $model->translate('slug', 'fr'));
    }

    public function test_can_override_sluggable_attribute()
    {
        $model = TestModelCustomAttribute::create(['title' => 'My test title']);

        $this->assertSame('my-test-title', $model->url);
    }

    public function test_can_generate_translated_url_for_route()
    {
        $route = new \Illuminate\Routing\Route('GET', '/foo/{testmodel}/{something}', function(TestModelTranslated $testmodel) {});

        $model = TestModelTranslated::create([
            'title' => [
                'en' => 'English title',
                'fr' => 'French title'
            ]
        ]);

        $this->get('/foo/english-title/some-value');

        $route->bind(request());

        $this->assertSame('/foo/french-title/some-value', $model->getSluggedUrlForRoute($route, 'fr', false));
    }
}
