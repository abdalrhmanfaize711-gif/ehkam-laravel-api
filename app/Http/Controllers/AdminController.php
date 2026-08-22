<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminModel;
use  Illuminate\Support\Facades\Hash;
class AdminController extends Controller
{
    function set_data_admine(Request $request){
        $admin =AdminModel::create([
            'username' => $request->username,
            'password' =>Hash::make($request->password)
        ]);
        return $admin;
    }
    
      function delete_admin(Request $request){
         $records = AdminModel::findOrfail($request->id);
         $records->delete();
         return  response()->json(['message' => 'تم الحذف بنجاح'], 201);
        }
   
    
}
