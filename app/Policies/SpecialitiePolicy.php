<?php

namespace App\Policies;

use App\Models\Specialities;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpecialitiePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('list_speciality')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Specialities $specialities = null): bool
    {
        if($user->can('edit_specialty')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->can('register_specialty')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Specialities $specialities = null): bool
    {
        if($user->can('edit_specialty')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Specialities $specialities = null): bool
    {
        if($user->can('delete_specialty')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Specialities $specialities = null): bool
    {
        if($user->can('delete_specialty')){
            return true;
        }
        return false;
    }

}
