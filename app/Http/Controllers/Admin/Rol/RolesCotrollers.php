<?php

namespace App\Http\Controllers\Admin\Rol;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;


class RolesCotrollers extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {  
        if(!auth('api')->user()->can('list_rol')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        // Filtro por nombre de rol
        $name = $request->search;

        $roles = Role::where("name", "like", "%".$name."%")->orderBy("id", "desc")->get();
        return response()->json([
            "roles" => $roles->map(function($rol){
                return [
                    "id" => $rol->id,
                    "name" => $rol->name,
                    "permision" => $rol->permissions,
                    "permision_pluck" => $rol->permissions->pluck("name"),
                    "created_at" => $rol->created_at->format("Y-m-d h:i:s")
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!auth('api')->user()->can('register_rol')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

       
        $existsRole = Role::where("name", $request->name)->first();

        if( $existsRole){
            return response()->json([
                "message" => 403,
                "message_text" => 'El nombre del rol ya existe'
            ]);
        }
        $newRole = Role::create([
            'guard_name' => 'api',
            'name' =>  $request->name]);

        // ["register", "edit_rol", "register_patient"]
        foreach ($request->permissions as $key => $permision) {
            $newRole->givePermissionTo($permision);
        }
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if(!auth('api')->user()->can('edit_rol')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }
        
       
        $role = Role::findOrFail($id);
        return response()->json([
            "id" => $role->id,
            "name" => $role->name,
            "permision" => $role->permissions,
            "permision_pluck" => $role->permissions->pluck("name"),
            "created_at" => $role->created_at->format("Y-m-d h:i:s")
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if(!auth('api')->user()->can('edit_rol')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

       
        $existsRole = Role::where("id", "<>", $id)->where("name", $request->name)->first();

        if( $existsRole){
            return response()->json([
                "message" => 403,
                "message_text" => 'El nombre del rol ya existe'
            ]);
        }
        $roleUpdate = Role::findOrFail($id);
        $roleUpdate->update($request->all());
        $roleUpdate->syncPermissions($request->permissions);
            
        return response()->json([
            "message" => 200,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if(!auth('api')->user()->can('delete_rol')){
            return response()->json([
                'message' => "403 | THIS ACTION IS UNAUTHORIZED."
            ], 403);
        }

        
        $roleRemove = Role::findOrFail($id);
        

        if( $roleRemove->users->count() > 0 ){
            return response()->json([
                'message' => 403,
                'menssage_text' => 'El rol que quiere eliminar tiene usuarios asignados'
            ]);
        }

        $resp = $roleRemove->delete();
        if($resp){
            return response()->json([
                "message" => 200,
                "message_text" => 'Role remove successufully'
            ]);
        }
    }
}
