<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Concerns\HandlesDeleteEvents;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\HasReplicatesWithRelation;
use Anil\FastApiCrud\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TraitUsesModel extends Model
{
    use HandlesDeleteEvents;
    use HasDateScopes;
    use HasReplicatesWithRelation;
    use HasUuid;
}
