<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Concerns\AnonymizesOnDelete;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\ReplicatesWithRelations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TraitUsesModel extends Model
{
    use AnonymizesOnDelete;
    use HasDateScopes;
    use HasUuids;
    use ReplicatesWithRelations;
}
