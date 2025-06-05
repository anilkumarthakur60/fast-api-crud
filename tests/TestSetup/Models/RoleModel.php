<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Traits\HandlesDeleteEvents;
use Anil\FastApiCrud\Traits\HasDateScopes;
use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Anil\FastApiCrud\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HandlesDeleteEvents;
    use HasDateScopes;
    use HasReplicatesWithRelation;
    // use HasUuid;

    protected $table = 'roles';

    protected $fillable = [
        'name',
    ];
}
