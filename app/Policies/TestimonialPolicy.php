<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Testimonial;

class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Testimonial $testimonial): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return true;
    }
    public function update(User $user, Testimonial $testimonial): bool
    {
        return true;
    }
    public function delete(User $user, Testimonial $testimonial): bool
    {
        return true;
    }
    public function deleteAny(User $user): bool
    {
        return true;
    }
    public function restore(User $user, Testimonial $testimonial): bool
    {
        return true;
    }
    public function forceDelete(User $user, Testimonial $testimonial): bool
    {
        return true;
    }
}
