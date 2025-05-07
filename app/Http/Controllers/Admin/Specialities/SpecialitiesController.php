<?php

namespace App\Http\Controllers\Admin\Specialities;

use App\Http\Controllers\Controller;
use App\Models\Doctor\Specialities;
use Illuminate\Http\Request;

class SpecialitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index( Request $request )
    {
        $this->authorize('viewAny', Specialitie::class);

        // Filtro por nombre de rol
        $name = $request->search;

        $specialities = Specialities::where("name", "like", "%".$name."%")->orderBy("id", "desc")->get();
        return response()->json([
            "specialities" => $specialities->map(function($speciality){
                return [
                    "id" => $speciality->id,
                    "name" => $speciality->name,
                    "state" => $speciality->state,
                    "created_at" => $speciality->created_at->format("d-m-Y")
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Specialitie::class);

        $existsSpeciality = Specialities::where("name", $request->name)->first();

        if( $existsSpeciality){
            return response()->json([
                "message" => 403,
                "message_text" => 'El nombre de la especialidad ya existe'
            ]);
        }
        $newSpeciality = Specialities::create([
            'name' =>  $request->name]);


        return response()->json([
            "message" => 200,
            "message_text" => 'Especialidad guardada correctamente'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('view', Specialitie::class);

        $speciality = Specialities::findOrFail($id);
        
        return response()->json( [
            'speciality' => $speciality
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('update', Specialitie::class);

        $existsSpeciality = Specialities::where('name', $request->name)
            ->where('id', '<>', $id) 
            ->first();

        if($existsSpeciality){
            return response()->json([
            'message' => 403,
            'message_text' => 'Ya existe una especialidad con ese nombre'
            ]);
        }

        $speciality = Specialities::findOrFail($id);
        $speciality->update($request->all());

        return response()->json([
            'message' => 200,
            'message_text' => 'Especialidad actualizada correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('delete', Specialitie::class);

        $speciality = Specialities::findOrFail($id);
        $speciality->delete();

        return response()->json([
            'message' => 200,
            'message_text' => 'Especialidad borrada correctamente'
        ]);
    }
}
