<?php

// app/Models/Group.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'created_by',
        'max_members',
        'is_private'
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
                    ->withPivot(['role', 'joined_at'])
                    ->withTimestamps();
    }

    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(GroupMessage::class)->latestOfMany();
    }

    public function isAdmin(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)
                   ->wherePivot('role', 'admin')->exists();
    }

    public function isModerator(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)
                   ->whereIn('role', ['admin', 'moderator'])->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function getMemberCount(): int
    {
        return $this->members()->count();
    }
}