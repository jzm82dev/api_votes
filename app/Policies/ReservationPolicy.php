<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if( $user->can('view_calendar_reservation')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        if( $user->can('view_calendar_reservation')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function viewReservationsToday(User $user): bool
    {
        if( $user->can('view_reservation')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if( $user->can('register_reservation')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        if( $user->can('edit_reservation')){
            return true;
        }
        return false;
    }

     /**
     * Determine whether the user can update the model.
     */
    public function updateRecurrent(User $user): bool
    {
        if( $user->can('edit_recurrent_reservation')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        if($user->can('delete_reservation')){
           return true;
        }
        return false;
    }

     /**
     * Determine whether the user can delete the model.
     */
    public function deleteRecurrent(User $user): bool
    {
        if($user->can('delete_recurrent_reservation')){
           return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        //
    }
}
