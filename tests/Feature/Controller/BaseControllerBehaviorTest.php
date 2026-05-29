<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\TagModel;

describe('base_controller_behaviour', function () {
    it('toggles a boolean status column', function () {
        $tag = TagModel::factory()->create(['status' => 1]);

        $this->putJson(route('tags.changeStatus', $tag->id))->assertOk();
        expect((int) $tag->fresh()->status)->toBe(0);

        $this->putJson(route('tags.changeStatus', $tag->id))->assertOk();
        expect((int) $tag->fresh()->status)->toBe(1);
    });

    it('returns 404 when updating a missing record', function () {
        $this->putJson(route('tags.update', 999999), [
            'name' => 'Ghost',
            'desc' => 'Ghost description',
            'status' => 1,
            'active' => 1,
        ])->assertNotFound();
    });

    it('returns 404 when deleting a missing record', function () {
        $this->deleteJson(route('tags.destroy', 999999))->assertNotFound();
    });

    it('returns 404 when showing a missing record', function () {
        $this->getJson(route('tags.show', 999999))->assertNotFound();
    });
});
