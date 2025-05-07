<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentPay;
use App\Http\Resources\Appointment\Pay\AppointmentPayCollection;

class AppointmentPayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request)
    {

        $this->authorize('viewAny', AppointmentPay::class);

        $specialitieId = $request->specialitie_id;
        $doctorName = $request->doctor_name_search;
        $patientName = $request->patient_name_search;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $user = auth('api')->user();

        $appointments = Appointment::filterAvancedPay($specialitieId, $doctorName, $patientName, $dateFrom, $dateTo, $user )
                                    ->orderBy("status_pay", "desc")
                                    ->orderBy('id', 'asc')
                                    ->paginate(20);
        /*
            $appointments = Appointment::where("specialitie_id", $specialitieId)
                                   ->whereHas("doctor", function($q) use($name_doctor){
                                        $q->where("name", "like", "%".$name_doctor."%")
                                        ->orWhere("surname", "like", "%".$name_doctor."%") ;
                                        })
                                    ->whereDate('date_appointment', Carbon::parse($date)->format("Y-m-d"));
        */
        return response()->json([
            'message' => 200,
            'total' => $appointments->total(),
            'appointments' => AppointmentPayCollection::make($appointments)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $appointment = Appointment::findOrFail($request->appointment_id);
        $this->authorize('addPayment', $appointment);

        //$totalPaid = $appointment->payments->sum('amount');
        $totalPaid = AppointmentPay::where("appointment_id", $request->appointment_id)->sum('amount');
        if( $totalPaid + $request->amount > $appointment->amount){
            return response()->json([
                'message' => 403,
                'message_text' => 'El total a pagar no puede ser menor que la suma de los pagos'
            ]);
        }


        $appointmentPay = AppointmentPay::create([
            'appointment_id' => $request->appointment_id,
            'amount' => $request->amount,
            'method_payment' => $request->method
        ]);   

        $updatedStatus = $appointment->status_pay;
        if($appointmentPay && $totalPaid + $request->amount == $appointment->amount){
           $updatedStatus = 1;
           $appointment->update([
                'status_pay' => 1
            ]);
        }

        return response()->json([
            "message" => 200,
            "update_status" => $updatedStatus,
            "appointment_pay" => [
                'id' => $appointmentPay->id,
                'appointment_id' => $appointmentPay->appointment_id,
                'amount' => $appointmentPay->amount,
                'method_payment' => $appointmentPay->method_payment,
                'created_at' => $appointmentPay->created_at->format("Y-m-d h:i:A"),
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appointmentPayUpdate = AppointmentPay::findOrFail($id);

        $this->authorize('update', $appointmentPayUpdate);

        $appointment = Appointment::findOrFail($request->appointment_id);
        //$totalPaid = $appointment->payments->sum('amount');
        $totalPaid = AppointmentPay::where("appointment_id", $request->appointment_id)->sum('amount');
        $totalPaid -= $appointmentPayUpdate->amount;
        if( $totalPaid + $request->amount > $appointment->amount){
            return response()->json([
                'message' => 403,
                'message_text' => 'El total a pagar no puede ser menor que la suma de los pagos'
            ]);
        }

        $updatedStatus = '';
        if($totalPaid + $request->amount == $appointment->amount){
            $updatedStatus = 1;
        }else{
            $updatedStatus = 2;
        }
        if( $updatedStatus != $appointment->status_pay)
        $appointment->update([
                'status_pay' => 1
            ]);
        
        
        $appointmentPayUpdate->update([
            'amount' => $request->amount,
            'method_payment' => $request->method
        ]);   

        return response()->json([
            "message" => 200,
            "update_status" => $updatedStatus,
            "appointment_pay" => [
                'id' => $appointmentPayUpdate->id,
                'appointment_id' => $appointmentPayUpdate->appointment_id,
                'amount' => $appointmentPayUpdate->amount,
                'method_payment' => $appointmentPayUpdate->method_payment,
                'created_at' => $appointmentPayUpdate->created_at->format("Y-m-d h:i:A"),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteAppointmentPay(string $id)
    {
        $appointmentPay = AppointmentPay::findOrFail($id);
        $this->authorize('delete', $appointmentPay);
        $appointment = Appointment::findOrFail( $appointmentPay->appointment_id);
        
        $appointmentPay->delete();
        $totalPaid = AppointmentPay::where("appointment_id", $appointmentPay->appointment_id)->sum('amount');

        if( $totalPaid < $appointment->amount){
            $appointment->update([
                'status_pay' => 2
            ]);
        }

        return response()->json([
            "message" => 200
        ]);

    }
}
