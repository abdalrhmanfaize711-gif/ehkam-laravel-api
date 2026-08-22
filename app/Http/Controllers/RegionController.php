<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegionModel;
use Illuminate\Support\Facades\DB;
class RegionController extends Controller
{
   public function add_region(Request $request)
{
    DB::beginTransaction();

    try {

        $data = $request->all();

        // الحالة الأولى: إرسال regions
        if (isset($data['regions']) && is_array($data['regions'])) {
            $regionsData = $data['regions'];
        }

        // الحالة الثانية: إرسال Array مباشرة
        elseif (array_is_list($data)) {
            $regionsData = $data;
        }

        // الحالة الثالثة: منطقة واحدة
        elseif (isset($data['name'])) {
            $regionsData = [$data];
        }

        else {
            return response()->json([
                'message' => 'صيغة البيانات غير صحيحة',
                'example' => [
                    'name' => 'عدن'
                ]
            ], 422);
        }

        $result = [];

        foreach ($regionsData as $regionData) {

            if (!is_array($regionData) || !isset($regionData['name'])) {
                throw new \Exception('كل منطقة يجب أن تحتوي على name');
            }

            $region = RegionModel::create([
                'name' => trim($regionData['name']),
            ]);

            $result[] = $region;
        }

        DB::commit();

        return response()->json([
            'message' => 'تم إضافة المنطقة/المناطق بنجاح',
            'regions' => $result,
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'message' => 'حدث خطأ أثناء إضافة المنطقة/المناطق',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    function get_all_region(Request $request){
         $region = RegionModel::all();
         return  response()->json(['message' => 'تم استرجاع المناطق بنجاح', 'region' => $region], 201);
        }

    function update_region(Request $request){
        $region = RegionModel::findOrfail($request->id);
       $region->update([
         'name'=>$request->name,  
        ]);
      
       

        return response()->json(['message' => 'تم تحديث سجل المنطقة بنجاح', 'region' => $region], 201);
     
    }

    function delete_region(Request $request){
         $region = RegionModel::findOrFail($request->id);
         $region->delete();
         return  response()->json(['message' => 'تم حذف المنطقة بنجاح'], 201);
        }

    function get_special_region(Request $request){
         $region = RegionModel::findOrFail($request->id);
         return  response()->json(['message' => 'تم استرجاع المنطقة بنجاح', 'region' => $region], 201);
    }
}
