<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BlogTag;

class BlogTagPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, BlogTag $record): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, BlogTag $record): bool
    {
        return true;
    }
    public function delete(User $user, BlogTag $record): bool
    {
        return true;
    }
    public function deleteAny(User $user): bool
    {
        return true;
    }
}
