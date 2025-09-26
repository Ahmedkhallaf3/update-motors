<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Illuminate\Http\Request;

class CarModelController extends Controller
{

    public function getByMakeId($makeId)
{
    $sortLang = strtolower(request()->get('sort', 'ar'));
    $orderColumn = ($sortLang === 'en') ? 'value_en' : 'value_ar';

    // جلب البيانات بدون ترتيب من قاعدة البيانات
    $models = CarModel::where('category_field_id', $makeId)->get();

    // ترتيب يدوي باستخدام الأحرف العربية فقط
    $models = $models->sortBy(function ($item) use ($orderColumn) {
        $text = $item[$orderColumn];

        // إزالة الأرقام وتحويل لـ lowercase
        return preg_replace('/[\x00-\x7F]+/', '', $text) . strtolower($text);
    });

    return response()->json($models->values()->all());
}

}
