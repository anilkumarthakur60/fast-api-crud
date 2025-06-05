<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Resources;

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostModel
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'name' => $this['name'],
            'desc' => $this['desc'],
            'user_id' => $this['user_id'],
            'status' => $this['status'],
            'active' => $this['active'],
            'created_at' => $this['created_at'],
            'updated_at' => $this['updated_at'],
        ];
    }
}
