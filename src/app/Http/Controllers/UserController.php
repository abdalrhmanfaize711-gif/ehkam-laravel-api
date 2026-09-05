<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
class UserController extends Controller
{
    function set_data_user(Request $request){
        $user =User::create([
            'name' => $request->name,
            'barthdate' => $request->barthdate,
            'region' => $request->region,
            'join_date' => $request->join_date,
            'role' => $request->role
        ]);
        return $user;
    }
    function get_data_user(Request $request){
        $user = User::all();
        return $user;
    }

    function delete_user(Request $request, $id){
        $user = User::find($id);
        $user->delete();
        return " succsse deleted";
    }

    function update_user(Request $request){
       
        $user =User::findOrfail($request->id);
        $user->name = $request->name;

        $user->save();
        $update = User::all( );
        return $update;
    }
        function get_one_user(Request $request){
        $user = User::findOrFail($request->user_id);
        return response()->json(['massage' =>'User',
        [ 'user'=> $user,
          'join_date'=>$user->join_date
        ] ]);
    }
}
