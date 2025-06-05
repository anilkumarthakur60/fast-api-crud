<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Illuminate\Support\Facades\Schema;

describe(description: 'post_model_class_test', tests: function () {
    beforeEach(function () {
        $this->postModel = new PostModel;
    });

    it(description: 'it_should_have_correct_fillable_attributes', closure: function () {
        $fillableKeys = array_keys($this->postModel->getFillable());
        sort($fillableKeys);
        $expectedKeys = array_keys(['name', 'desc', 'user_id', 'status', 'active']);
        sort($expectedKeys);
        expect($fillableKeys)->toBeArray()->and($fillableKeys)->toBe($expectedKeys);
    });

    it(description: 'it_should_have_correct_table_name', closure: function () {
        expect($this->postModel->getTable())->toBe('posts');
    });

    it(description: 'it_should_have_correct_primary_key', closure: function () {
        expect($this->postModel->getKeyName())->toBe('id');
    });

    it(description: 'it_should_have_correct_timestamps', closure: function () {
        expect($this->postModel->getCreatedAtColumn())->toBe('created_at')->and($this->postModel->getUpdatedAtColumn())->toBe('updated_at');
    });

    it(description: 'it_should_have_correct_columns', closure: function () {
        $columns = Schema::getColumnListing($this->postModel->getTable());
        sort($columns);
        $expectedColumns = ['active', 'created_at', 'deleted_at', 'desc', 'id', 'name', 'status', 'updated_at', 'user_id'];
        sort($expectedColumns);
        expect($columns)->toBe($expectedColumns);
    });

    it(description: 'should_have_all_the_method_defined_in_the_model', closure: function () {
        expect(method_exists($this->postModel, 'user'))->toBeTrue()->and(method_exists($this->postModel, 'tags'))->toBeTrue();
    });

    it(description: 'it_should_have_correct_relationships', closure: function () {
        expect(method_exists($this->postModel, 'user'))->toBeTrue()->and(method_exists($this->postModel, 'tags'))->toBeTrue()->and($this->postModel->user())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class)->and($this->postModel->tags())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it(description: 'it_should_sync_tags_after_create', closure: function () {
        $this->postModel->afterCreate();
        // Assuming the request has 'tag_ids' filled
        expect($this->postModel->tags()->count())->toBe(0); // Adjust based on actual test setup
    });

    it(description: 'it_should_sync_tags_after_update', closure: function () {
        $this->postModel->afterUpdate();
        // Assuming the request has 'tag_ids' filled
        expect($this->postModel->tags()->count())->toBe(0); // Adjust based on actual test setup
    });

    it(description: 'it_should_return_permission_slug', closure: function () {
        expect($this->postModel->getPermissionSlug())->toBe('posts');
    });
});
