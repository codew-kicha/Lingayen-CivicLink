<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    protected $fillable = ['organization_id', 'name', 'position', 'contact_number', 'email'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
