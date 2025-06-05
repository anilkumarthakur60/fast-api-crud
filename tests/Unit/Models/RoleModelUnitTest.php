<?php

namespace Tests\Unit\Models;

use Anil\FastApiCrud\Tests\TestSetup\Models\RoleModel;
use Anil\FastApiCrud\Traits\HandlesDeleteEvents;
use Anil\FastApiCrud\Traits\HasDateScopes;
use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;

describe('RoleModelUnitTest', function () {
    it('has used traits', function () {
        expect(class_uses(RoleModel::class))->toContain(HasDateScopes::class)
            ->and(class_uses(RoleModel::class))->toContain(HandlesDeleteEvents::class)
            ->and(class_uses(RoleModel::class))->toContain(HasReplicatesWithRelation::class);
    });
});
