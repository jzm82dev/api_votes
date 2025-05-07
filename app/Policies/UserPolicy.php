<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Club\Club;
use App\Models\Member\ClubUser;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('list_staff')){
            return true;
        }
        return false;
    }

    /**
     * Begin determine permissions Monitors.
     */
    public function viewAnyMonitor(User $user): bool
    {
        if($user->can('list_monitor')){
            return true;
        }
        return false;
    }

    public function createMonitor(User $user): bool
    {
        if($user->can('register_monitor')){
            return true;
        }
        return false;
    }

    public function viewMonitor(User $user, User $monitor): bool
    {

        if($user->can('edit_monitor')){
            if($user->club_id == $monitor->club_id){
                return true;
            }
        }
        return false;
    }

    public function editMonitor(User $user, User $monitor): bool
    {

        if($user->can('edit_monitor')){
            if($user->club_id == $monitor->club_id){
                return true;
            }
        }
        return false;
    }

    public function deleteMonitor(User $user, User $monitor): bool
    {

        if($user->can('delete_monitor')){
            if($user->club_id == $monitor->club_id){
                return true;
            }
        }
        return false;
    }
    
    /**
     * End determine permissions Monitors.
     */


    /**
     * Begin determine permissions Members.
     */
    public function viewAnyMember(User $user): bool
    {
        if($user->can('list_member')){
            return true;
        }
        return false;
    }

    public function createMember(User $user): bool
    {
        if($user->can('register_member')){
            return true;
        }
        return false;
    }

    public function viewMember(User $user, User $member): bool
    {

        if($user->can('edit_member')){
            if(ClubUser::where('user_id', $member->id)->where('club_id', $user->club_id)->exists() ){
                return true;
            }
        }
        return false;
    }

    public function editMember(User $user, User $member): bool
    {
        return true;
        if($user->can('edit_member')){
            if(ClubUser::where('user_id', $member->id)->where('club_id', $user->club_id)->exists() ){
                return true;
            }
        }
        return false;
    }

    public function deleteMember(User $user, User $member): bool
    {

        if($user->can('delete_member')){
            if(ClubUser::where('user_id', $member->id)->where('club_id', $user->club_id)->exists() ){
                return true;
            }
        }
        return false;
    }
    
    /**
     * End determine permissions Monitors.
     */



    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model = null): bool
    {
        if($user->can('edit_staff')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->can('register_staff')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model = null): bool
    {
        if($user->can('edit_staff')){
            return true;
        }
        return false;
    }
    

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model = null): bool
    {
        if($user->can('delete_staff')){
            return true;
        }
        return false;
    }

  
}
