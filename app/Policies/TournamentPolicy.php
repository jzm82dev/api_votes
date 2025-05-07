<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\Tournament\Tournament;
use Spatie\Permission\PermissionRegistrar;

class TournamentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if($user->can('list_tournament')){
           return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */

    public function view(User $user, Tournament $tournament): bool
    {

        if($user->can('edit_tournament')){
            if($user->club_id == $tournament->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->can('register_tournament')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tournament $tournament): bool
    {
        if($user->can('edit_tournament')){
            if($user->club_id == $tournament->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tournament $tournament): bool
    {
        if($user->can('delete_tournament')){
            if($user->club_id == $tournament->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function configure(User $user, Tournament $tournament): bool
    {
        if($user->can('edit_tournament')){
            if($user->club_id == $tournament->club_id){
                return true;
            }
        }
        return false;
    }

}
