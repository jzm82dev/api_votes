<?php

namespace App\Http\Controllers\League;

use Illuminate\Http\Request;
use App\Models\Couple\Couple;
use App\Models\League\League;
use App\Http\Controllers\Controller;
use App\Http\Resources\Couple\CoupleCollection;

class CoupleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', League::class);
     
        $search = $request->search;
        
        $teams = Couple::where("name", 'like', '%'.$search.'%')
        ->orderBy('id', 'desc')
        ->paginate(20);

        return response()->json([
            "total" => $teams->total(),
            "teams" => CoupleCollection::make($teams)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
