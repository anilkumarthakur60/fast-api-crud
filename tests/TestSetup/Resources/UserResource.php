<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Resources;

use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin UserModel
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read int $status
 * @property-read int $active
 * @property-read string $created_at
 * @property-read string $updated_at
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'active' => $this->active,
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i'),
            'updated_at' => Carbon::parse($this->updated_at)->format('Y-m-d H:i'),
        ];
    }
}
