<?php

namespace Tests\Unit\Models;

use Anil\FastApiCrud\Concerns\HandlesDeleteEvents;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\HasReplicatesWithRelation;
use Anil\FastApiCrud\Tests\TestSetup\Models\RoleModel;

describe('RoleModelUnitTest', function () {
    it('has used traits', function () {
        expect(class_uses(RoleModel::class))->toContain(HasDateScopes::class)
            ->and(class_uses(RoleModel::class))->toContain(HandlesDeleteEvents::class)
            ->and(class_uses(RoleModel::class))->toContain(HasReplicatesWithRelation::class);
    });
});
