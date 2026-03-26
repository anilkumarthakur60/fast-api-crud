<?php

namespace Tests\Unit\Models;

use Anil\FastApiCrud\Concerns\AnonymizesOnDelete;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\ReplicatesWithRelations;
use Anil\FastApiCrud\Tests\TestSetup\Models\RoleModel;

describe('RoleModelUnitTest', function () {
    it('has used traits', function () {
        expect(class_uses(RoleModel::class))->toContain(HasDateScopes::class)
            ->and(class_uses(RoleModel::class))->toContain(AnonymizesOnDelete::class)
            ->and(class_uses(RoleModel::class))->toContain(ReplicatesWithRelations::class);
    });
});
