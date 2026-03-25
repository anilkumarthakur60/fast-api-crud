<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Concerns\HandlesDeleteEvents;
use Anil\FastApiCrud\Concerns\HasDateScopes;
use Anil\FastApiCrud\Concerns\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HandlesDeleteEvents;
    use HasDateScopes;
    use HasReplicatesWithRelation;

    protected $table = 'roles';

    protected $fillable = [
        'name',
    ];
}
