<?php

namespace App\Policies;

use App\Models\Patient\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('list_patient')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient = null): bool
    {
        if($user->can('edit_patient')){
            return true;
        }
        return false;
    }

      /**
     * Determine whether the user can view the model.
     */
    public function profile(User $user, Patient $patient = null): bool
    {
        if($user->can('profile_patient')){
            return true;
        }
        return false;
    }


    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->can('register_patient')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient = null): bool
    {
        if($user->can('edit_patient')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient = null): bool
    {
        if($user->can('delete_patient')){
            return true;
        }
        return false;
    }

}
