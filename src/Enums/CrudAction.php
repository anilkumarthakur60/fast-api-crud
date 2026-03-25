<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Enums;

/**
 * Represents the standard CRUD actions used for
 * permission middleware registration.
 */
enum CrudAction: string
{
    case View = 'view';
    case Store = 'store';
    case Update = 'update';
    case Delete = 'delete';
    case ChangeStatus = 'change-status';
    case Restore = 'restore';
}
