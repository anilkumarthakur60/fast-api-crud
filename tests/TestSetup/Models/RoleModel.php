<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Models;

use Anil\FastApiCrud\Tests\TestSetup\Factories\RoleModelFactory;
use Anil\FastApiCrud\Traits\HandlesDeleteEvents;
use Anil\FastApiCrud\Traits\HasDateScopes;
use Anil\FastApiCrud\Traits\HasReplicatesWithRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModel extends Model
{
    use HandlesDeleteEvents;
    use HasDateScopes;
    /** @use HasFactory<RoleModelFactory> */
    use HasFactory;

    use HasReplicatesWithRelation;

    protected $table = 'roles';

    protected $fillable = [
        'name',
    ];
}
