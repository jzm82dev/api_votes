<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Appointment\Appointment;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {

        if($user->can('list_appointment')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Appointment $appointment): bool
    {
      
        if($user->can('edit_appointment')){
            if( $user->id == $appointment->user_id){
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function addPayment(User $user, Appointment $appointment): bool
    {
        
        if($user->can('add_payment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }


    public function updateAttentionAppoinment(User $user, Appointment $appointment): bool
    {
        if($user->can('attention_appointment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointment->user_id){
                    return true;
                }
            }
        }
    }

     /**
     * Determine whether the user can filter the model.
     */
    public function filter(User $user, Appointment $appointment = null): bool
    {
        if($user->can('list_appointment') || $user->can('edit_appointment')){
            return true;
        }
        return false;
    }

       /**
     * Determine whether the user can filter the model.
     */
    public function show(User $user, Appointment $appointment): bool
    {
        if($user->can('edit_appointment') && $user->id == $appointment->user_id){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if($user->can('register_appointment')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if($user->can('edit_appointment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        if($user->can('edit_appointment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }

}
