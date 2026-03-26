<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Concerns\AnonymizesOnDelete;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\HasUuidPrimaryKey;
use Anil\FastApiCrud\Concerns\ReplicatesWithRelations;
use Illuminate\Database\Eloquent\Model;

class TraitUsesModel extends Model
{
    use AnonymizesOnDelete;
    use HasDateScopes;
    use HasUuidPrimaryKey;
    use ReplicatesWithRelations;
}
