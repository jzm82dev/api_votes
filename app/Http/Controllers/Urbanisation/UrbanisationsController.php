<?php

namespace App\Http\Controllers\Urbanisation;

use App\Http\Controllers\Controller;
use App\Http\Resources\Urbanisation\UrbanisationCollection;
use App\Models\Location\City;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\Urbanisation\Urbanisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UrbanisationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        
        $urbanisations = Urbanisation::where(DB::raw("CONCAT(urbanisations.name)") , 'like', '%'.$search.'%')
                     ->orderBy('id', 'desc')
                     ->paginate(20);

        return response()->json([
            "total" => $urbanisations->total(),
            "urbanisations" => UrbanisationCollection::make($urbanisations)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
            'address' => 'required|max:191',
            'additional_address' => 'max:191',
            'postal_code' => 'required|max:191',
            'country_id' => 'required|integer',
            'city_id' => 'required|integer'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());
            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
        }

         $validUrbanisation = Urbanisation::where('name', $request->name)
                        ->first();

        if($validUrbanisation){
            return response()->json([
                'message' => 403,
                'message_text' => 'Ya existe una urbanización con este nombre'
            ]);
        }
        if($request->hasFile('imagen')){
            $path = Storage::putFile("urbanisations", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }

        $hash = str()->random(5);
        
        $request->request->add(['hash' => $hash]);
        $club = Urbanisation::create($request->all());
        
        
        return response()->json([
            'message' => 200, 
            'urbanisation_id' => $club->id
        ]);
        
    }

    

    public static function config( ){

       $countries = Country::where('id', 207)->get(['id', 'name']);

        return response()->json([
            'message' => 200,
            'countries' => $countries
        ]);
    }


    public static function getStates( string $id){

        $states = State::where('country_id', $id)->orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json([
            'message' => 200,
            'country_id' => $id,
            'states' => $states
        ]);
    }

    public static function getCities( string $id){

        $states = City::where('state_id', $id)->orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json([
            'message' => 200,
            'country_id' => $id,
            'cities' => $states
        ]);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $urbanisation = Urbanisation::findOrFail($id);
        $urbanisation->avatar = $urbanisation->avatar ? env("APP_URL")."storage/".$urbanisation->avatar : 'assets/img/user-06.jpg';
        return response()->json( [
            'message' => 200,
            'urbanisation' => $urbanisation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $urbanisation = Urbanisation::findOrFail($id);
     

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'mobile' => 'required|max:50', //|digits:9',
            'email' => 'email|required|max:191',
            'address' => 'required|max:191',
            'additional_address' => 'max:191',
            'postal_code' => 'required|max:191',
            'country_id' => 'required|integer',
            'city_id' => 'required|integer'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

       /* $validMonitor = User::where('id', '<>', $id) 
                           ->where('mobile', $request->mobile)
                          // ->orWhere('email', $request->email)
                           ->first();
*/
        $validMonitor = Urbanisation::where(function ($query) use($id){
                            $query->where('id', '<>', $id);
                        })->where(function ($query) use($request){
                            $query->where('name', $request->name);
                        })->first();

        if($validMonitor){
            $errors[] = 'Ya existe un una urbanización con ese nombre'. $validMonitor->toSql();;
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }   

        if($request->hasFile('imagen')){
            if( $urbanisation->avatar){
               $result = Storage::delete($urbanisation->avatar);
            }
            $path = Storage::putFile("monitors", $request->file('imagen'));
            $request->request->add(['avatar' => $path]);
        }
        

        $urbanisation->update($request->all());


        return response()->json([
            'message' => 200,
            'message_text' => 'Urbanización actualizada correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
