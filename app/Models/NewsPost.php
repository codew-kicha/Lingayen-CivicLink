<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = ['author_id', 'title', 'slug', 'body', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'news_post_organization');
    }
}
