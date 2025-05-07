<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Auth\Access\Response;
use App\Models\Appointment\AppointmentPay;

class AppointmentPayPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->can('show_payment')){
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AppointmentPay $appointmentPay): bool
    {
        if($user->can('edit_payment')){ //} || $user->can('add_payment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointmentPay->appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointmentPay->appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AppointmentPay $appointmentPay): bool
    {
     
        if($user->can('edit_payment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){ //&& !$user->can('edit_payment')){ // El doctor no puede agregar un pago
                if( $user->id == $appointmentPay->appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointmentPay->appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AppointmentPay $appointmentPay): bool
    {
        if($user->can('delete_payment')){
            if(Str::contains(Str::upper($user->roles->first()->name), 'DOCTOR')){
                if( $user->id == $appointmentPay->appointment->doctor_id){
                    return true;
                }
            }else{
                if( $user->id == $appointmentPay->appointment->user_id){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AppointmentPay $appointmentPay): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AppointmentPay $appointmentPay): bool
    {
        //
    }
}
