<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet\VirtualWallet;
use Illuminate\Auth\Access\Response;

class VirtualWalletPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('list_wallet')){
            return true;
         }
         return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VirtualWallet $virtualWallet): bool
    {
        if($user->can('edit_wallet')){
            if($user->club_id == $virtualWallet->club_id){
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
        if($user->can('register_wallet')){
            return true;
         }
         return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VirtualWallet $virtualWallet): bool
    {
        if($user->can('edit_wallet')){
            if($user->club_id == $virtualWallet->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VirtualWallet $virtualWallet): bool
    {
        if($user->can('delete_wallet')){
            if($user->club_id == $virtualWallet->club_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VirtualWallet $virtualWallet): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VirtualWallet $virtualWallet): bool
    {
        //
    }
}
