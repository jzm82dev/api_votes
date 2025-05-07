<?php

namespace App\Policies;

use App\Models\League\League;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaguePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('list_league')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, League $league): bool
    {
        if($user->can('edit_league')){
            if($user->club_id == $league->club_id){
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
        if($user->can('register_league')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, League $league): bool
    {
        if($user->can('edit_league')){
            if($user->club_id == $league->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, League $league): bool
    {
        if($user->can('delete_league')){
            if($user->club_id == $league->club_id){
                return true;
            }
        }
        return false;
    }

}
