<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Faq;

class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Faq $record): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, Faq $record): bool
    {
        return true;
    }
    public function delete(User $user, Faq $record): bool
    {
        return true;
    }
    public function deleteAny(User $user): bool
    {
        return true;
    }
}
