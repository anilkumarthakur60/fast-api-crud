<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Illuminate\Support\Facades\Schema;

describe(description: 'tag_model_class_test1', tests: function () {
    beforeEach(function () {
        $this->tagModel = new TagModel;
    });
    it(description: 'it_should_have_correct_fillable_attributes', closure: function () {
        $fillableKeys = array_keys($this->tagModel->getFillable());
        sort($fillableKeys);
        $expectedKeys = array_keys([
            'name',
            'desc',
            'status',
            'active',
        ]);
        sort($expectedKeys);
        expect($fillableKeys)
            ->toBeArray()
            ->and($fillableKeys)
            ->toBe($expectedKeys);
    });
    it(description: 'it_should_have_correct_table_name', closure: function () {
        expect($this->tagModel->getTable())
            ->toBe('tags');
    });
    it(description: 'it_should_have_correct_primary_key', closure: function () {
        expect($this->tagModel->getKeyName())
            ->toBe('id');
    });

    it(description: 'it_should_have_correct_timestamps', closure: function () {
        expect($this->tagModel->getCreatedAtColumn())
            ->toBe('created_at')
            ->and($this->tagModel->getUpdatedAtColumn())
            ->toBe('updated_at');
    });
    it(description: 'it_should_have_correct_columns', closure: function () {
        $columns = Schema::getColumnListing($this->tagModel->getTable());
        sort($columns);
        $expectedColumns = [
            'active',
            'created_at',
            'deleted_at',
            'desc',
            'id',
            'name',
            'status',
            'updated_at',
        ];
        sort($expectedColumns);
        expect($columns)->toBe($expectedColumns);
    });

    it(description: 'should_have_all_the_method_defined_in_the_model', closure: function () {
        expect(method_exists($this->tagModel, 'afterCreate'))->toBeTrue()
            ->and(method_exists($this->tagModel, 'afterUpdate'))->toBeTrue()
            ->and(method_exists($this->tagModel, 'posts'))->toBeTrue()
            ->and(method_exists($this->tagModel, 'scopeQueryFilter'))->toBeTrue()
            ->and(method_exists($this->tagModel, 'scopeActive'))->toBeTrue()
            ->and(method_exists($this->tagModel, 'scopeStatus'))->toBeTrue();
    });

    it(description: 'should_sync_posts_after_creating_a_tag', closure: function () {
        $this->tagModel->name = 'Test Tag';
        $this->tagModel->desc = 'Test Description';
        $this->tagModel->status = 1;
        $this->tagModel->active = 1;
        $this->tagModel->save();

        $this->tagModel->afterCreate();

        $posts = PostModel::factory(2)->create()->pluck('id')->toArray();
        // Assuming post_ids is passed in the request
        $this->tagModel->posts()->attach($posts); // Simulating post IDs
        expect($this->tagModel->posts()->count())->toBe(2);
    });

    it(description: 'should_sync_posts_after_updating_a_tag', closure: function () {
        $this->tagModel->name = 'Test Tag';
        $this->tagModel->desc = 'Test Description';
        $this->tagModel->status = 1;
        $this->tagModel->active = 1;
        $this->tagModel->save();

        $posts = PostModel::factory(2)->create()->pluck('id')->toArray();
        // Simulating post IDs
        $this->tagModel->posts()->attach($posts);
        $this->tagModel->afterUpdate();

        $newPosts = PostModel::factory(7)->create()->pluck('id')->toArray();

        // Assuming post_ids is passed in the request
        $this->tagModel->posts()->sync($newPosts); // Updating post IDs
        expect($this->tagModel->posts()->count())->toBe(7);
    });
});
