<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BlogCategory;

class BlogCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, BlogCategory $record): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, BlogCategory $record): bool
    {
        return true;
    }
    public function delete(User $user, BlogCategory $record): bool
    {
        return true;
    }
    public function deleteAny(User $user): bool
    {
        return true;
    }
}
