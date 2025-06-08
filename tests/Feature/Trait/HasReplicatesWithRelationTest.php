<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\ChildModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\CommentModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\CountryModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\OwnerModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\ParentModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\PetModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\ProfileModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;
use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Support\Collection;

describe('has_replicates_with_relation_trait', function () {

    /** @test */
    test('it_replicates_a_model_with_no_relations', function () {
        $parent = ParentModel::create(['name' => 'Original Parent']);
        $replicated = $parent->replicateWithRelations();

        // New parent exists with the same data but different ID
        expect($replicated->id)->not->toBe($parent->id);
        $this->assertDatabaseHas('parents', [
            'id' => $replicated->id,
            'name' => 'Original Parent',
        ]);

        // New parent has no children
        expect($replicated->children)->toBeInstanceOf(Collection::class)
            ->and($replicated->children->count())->toBe(0);
    });

    /** @test */
    test('it_replicates_has_many_and_belongs_to_relations', function () {
        $parent = ParentModel::create(['name' => 'Parent A']);
        $child1 = ChildModel::create(['title' => 'Child 1', 'parent_id' => $parent->id]);
        $child2 = ChildModel::create(['title' => 'Child 2', 'parent_id' => $parent->id]);

        $replicatedParent = $parent->replicateWithRelations();

        // Parent is replicated
        expect($replicatedParent->id)->not->toBe($parent->id);
        $this->assertDatabaseHas('parents', [
            'id' => $replicatedParent->id,
            'name' => 'Parent A',
        ]);

        // Each child is replicated and attached to a new parent
        $this->assertDatabaseHas('children', [
            'title' => 'Child 1',
            'parent_id' => $replicatedParent->id,
        ]);
        $this->assertDatabaseHas('children', [
            'title' => 'Child 2',
            'parent_id' => $replicatedParent->id,
        ]);

        // Original children remain
        $this->assertDatabaseHas('children', [
            'title' => 'Child 1',
            'parent_id' => $parent->id,
        ]);
        $this->assertDatabaseHas('children', [
            'title' => 'Child 2',
            'parent_id' => $parent->id,
        ]);
    });

    /** @test */
    test('it_replicates_has_one_and_belongs_to_relations', function () {
        $owner = OwnerModel::create(['name' => 'Alice']);
        $pet = PetModel::create(['name' => 'Fluffy', 'owner_id' => $owner->id]);

        $replicatedOwner = $owner->replicateWithRelations();

        // Owner is replicated
        expect($replicatedOwner->id)->not->toBe($owner->id);
        $this->assertDatabaseHas('owners', [
            'id' => $replicatedOwner->id,
            'name' => 'Alice',
        ]);

        // Pet is replicated and attached to new owner
        $this->assertDatabaseHas('pets', [
            'pet_name' => 'Fluffy',
            'owner_id' => $replicatedOwner->id,
        ]);

        // Original pet remains unchanged
        $this->assertDatabaseHas('pets', [
            'pet_name' => 'Fluffy',
            'owner_id' => $owner->id,
        ]);
    });

    /** @test */
    test('it_replicates_belongs_to_many_relations_without_replicating_related_models', function () {
        $post = PostModel::create(['post_title' => 'First Post']);
        $tagA = TagModel::create(['tag_name' => 'Laravel']);
        $tagB = TagModel::create(['tag_name' => 'PHP']);
        $post->tags()->sync([$tagA->id, $tagB->id]);

        $replicatedPost = $post->replicateWithRelations();

        // Post is replicated
        expect($replicatedPost->id)->not->toBe($post->id);
        $this->assertDatabaseHas('posts', [
            'id' => $replicatedPost->id,
            'post_title' => 'First Post',
        ]);

        // Pivot rows for tags exist for the new post
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $replicatedPost->id,
            'tag_id' => $tagA->id,
        ]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $replicatedPost->id,
            'tag_id' => $tagB->id,
        ]);

        // Original pivot rows remain
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tagA->id,
        ]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $tagB->id,
        ]);
    });

    /** @test */
    test('it_replicates_morph_one_and_morph_to_relations', function () {
        $user = UserModel::create(['name' => 'bob', 'email' => 'bob@example.com']);
        $profile = ProfileModel::create([
            'profilable_id' => $user->id,
            'profilable_type' => UserModel::class,
            'bio' => 'Bio text',
        ]);

        $replicatedUser = $user->replicateWithRelations();

        // User is replicated
        expect($replicatedUser->id)->not->toBe($user->id);
        $this->assertDatabaseHas('users', [
            'id' => $replicatedUser->id,
            'username' => 'bob',
        ]);

        // Profile is replicated and attached to new user
        $this->assertDatabaseHas('profiles', [
            'profilable_id' => $replicatedUser->id,
            'profilable_type' => UserModel::class,
            'bio' => 'Bio text',
        ]);

        // Original profile remains
        $this->assertDatabaseHas('profiles', [
            'profilable_id' => $user->id,
            'profilable_type' => UserModel::class,
            'bio' => 'Bio text',
        ]);
    });

    /** @test */
    test('it_replicates_morph_many_and_morph_to_relations_on_child', function () {
        $user = UserModel::create(['name' => 'charlie', 'email' => 'charlie@example.com']);
        $comment1 = CommentModel::create([
            'commentable_id' => $user->id,
            'commentable_type' => UserModel::class,
            'body' => 'Hello!',
        ]);
        $comment2 = CommentModel::create([
            'commentable_id' => $user->id,
            'commentable_type' => UserModel::class,
            'body' => 'Second comment',
        ]);

        $replicatedUser = $user->replicateWithRelations();

        // User is replicated
        expect($replicatedUser->id)->not->toBe($user->id);
        $this->assertDatabaseHas('users', [
            'id' => $replicatedUser->id,
            'username' => 'charlie',
        ]);

        // Comments are replicated and attached to new user
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $replicatedUser->id,
            'commentable_type' => UserModel::class,
            'body' => 'Hello!',
        ]);
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $replicatedUser->id,
            'commentable_type' => UserModel::class,
            'body' => 'Second comment',
        ]);

        // Original comments remain
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $user->id,
            'commentable_type' => UserModel::class,
            'body' => 'Hello!',
        ]);
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $user->id,
            'commentable_type' => UserModel::class,
            'body' => 'Second comment',
        ]);
    });

    /** @test */
    test('it_throws_exception_for_has_one_through_and_has_many_through_relations', function () {
        $country = CountryModel::create(['name' => 'Utopia']);
        $user = UserModel::create(['name' => 'dave', 'country_id' => $country->id, 'email' => 'dave@example.com']);
        $post = PostModel::create(['name' => 'Through Post', 'user_id' => $user->id]);
        $user->posts()->save($post);

        // Load the hasManyThrough relation before replicating
        $country->load('posts');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("HasManyThrough relationship 'posts' is not supported for replication.");
        $country->replicateWithRelations();
    });
})->only();
