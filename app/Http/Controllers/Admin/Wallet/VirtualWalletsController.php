<?php

namespace App\Http\Controllers\Admin\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Wallet\VirtualWallet;
use App\Models\Wallet\VirtualWalletSpent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VirtualWalletsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', VirtualWallet::class);

        $clubId = auth("api")->user()->club_id;
        $search = $request->search;
        $wallets = VirtualWallet::where(DB::raw("CONCAT(name, ' ',surname)") , 'like', '%'.$search.'%')->where('club_id', $clubId)
                     ->orderBy('name', 'asc')
                     ->get();

        return response()->json([
            "wallets" => $wallets
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', VirtualWallet::class);
        
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50',
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }
         
        $clubId = auth("api")->user()->club_id;

        $validUser = VirtualWallet::where(function ($query) use($clubId){
            $query->where('club_id', $clubId);
        })->where(function ($query) use($request){
            $query->where('mobile', $request->mobile)
                  ->orwhere(DB::raw("CONCAT(name, ' ',surname)") , 'like', $request->name.' '.$request->surname);
        })->first();

        if($validUser){
            $errors[] = 'Ya existe este usuario con monedero virtual';
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $request->request->add(['club_id' => $clubId]);
        
        $virtualWallet = VirtualWallet::create($request->all());
        
        
        
       
       
        return response()->json([
            'message' => 200,
            'wallet' => $virtualWallet,
            'message_text' => 'Vistual Wallet saved correctly'
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clubId = auth("api")->user()->club_id;
        $wallet = VirtualWallet::findOrFail($id);

        $this->authorize('view', $wallet);
        
        $spents = VirtualWalletSpent::where('virtual_wallet_id', $wallet->id)->orderBy('id', 'desc')->get();
        
        
        return response()->json( [
            'message' => 200,
            'wallet' => $wallet,
            'spents' => $spents,
        ]);
    }


    public function addSpent( Request $request)
    {

        $validator = Validator::make($request->all(),[
            'virtual_wallet_id' => 'required',
            'info' => 'required|max:191',
            'amount' => 'required|between:0,500'
        ]);


        $clubId = auth("api")->user()->club_id;
        $wallet = VirtualWallet::findOrFail($request->virtual_wallet_id);

        $this->authorize('view', $wallet);

        $wallet->subtractsSpent($request->amount);
        $virtualWalletSpent = VirtualWalletSpent::create($request->all());

        return response()->json([
            'message' => 200,
            'new_spent_wallet' => $virtualWalletSpent,
            'current_amount' => $wallet->amount,
            'message_text' => 'Vistual Wallet saved correctly'
        ]);
    }
    
    public function addRecharge( Request $request)
    {

        $validator = Validator::make($request->all(),[
            'virtual_wallet_id' => 'required',
            'info' => 'required|max:191',
            'amount' => 'required|between:0,500'
        ]);


        $clubId = auth("api")->user()->club_id;
        $wallet = VirtualWallet::findOrFail($request->virtual_wallet_id);

        $this->authorize('view', $wallet);

        $wallet->chargeExpense($request->amount);
        $virtualWalletSpent = VirtualWalletSpent::create($request->all());

        return response()->json([
            'message' => 200,
            'new_spent_wallet' => $virtualWalletSpent,
            'current_amount' => $wallet->amount,
            'message_text' => 'Vistual Wallet saved correctly'
        ]);
    }
    

    public function removeSpent( string $id)
    {

        $clubId = auth("api")->user()->club_id;
        $spent = VirtualWalletSpent::findOrFail($id);
        $wallet = VirtualWallet::findOrFail( $spent->virtual_wallet_id);

        $this->authorize('view', $wallet);

        switch ($spent->is_recharge) {
            case '1':
                $wallet->subtractsSpent($spent->amount);
                break;
            case '0':
                $wallet->chargeExpense($spent->amount);
                break;
            default:
                # code...
                break;
        }
      
        $spent->delete();

        return response()->json([
            'message' => 200,
            'current_amount_wallet' => $wallet->amount,
            'message_text' => 'Todo bien'
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $clubId = auth("api")->user()->club_id;
        $wallet = VirtualWallet::findOrFail($id);
        $this->authorize('update', $wallet);


        $validator = Validator::make($request->all(),[
            'name' => 'required|max:191',
            'surname' => 'required|max:191',
            'mobile' => 'required|max:50'
        ]);

        if($validator->fails()){
            $errors = get_errors($validator->errors());

            return response()->json([
                 'message' => 422,
                 'errors_text' => $errors
             ]);
         }

        $validWallet = VirtualWallet::where(function ($query) use($id, $clubId){
                $query->where('id', '<>', $id);
                $query->where('club_id', '=', $clubId);
            })->where(function ($query) use($request){
                $query->where('mobile', $request->mobile)
                ->orwhere(DB::raw("CONCAT(name, ' ',surname)") , 'like', $request->name.' '.$request->surname);
            })->first();

       

        if($validWallet){
            $errors[] = 'Ya existe un usuario con este nombre o teléfono';
            return response()->json([
                'message' => 422,
                'errors_text' => $errors
            ]);
        }

        $wallet->update($request->all());

        return response()->json([
            'message' => 200,
            'wallet' => $wallet,
            'message_text' => 'Wallet saved correctly'
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
       $wallet = VirtualWallet::findOrFail($id);
       $this->authorize('delete', $wallet);

       $deleteSpents = VirtualWalletSpent::where('virtual_wallet_id', '=', $wallet->id)->delete();
       $wallet->delete();

       return response()->json([
        'message' => 200
    ]);

    }
}
