<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\CategoryFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryFieldController extends Controller
{

    public function index(Request $request, $categoryId)
{
    $category = Category::with('fields.values')->findOrFail($categoryId);
    $sort = $request->query('sort', 'en');
    $fields = $category->fields;

    $requiredFields = [
        'Model Year' => 'سنة الصنع',
        'Make' => 'الشركة المصنعة',
    ];

    // إضافة الحقول المطلوبة لو غير موجودة
    foreach ($requiredFields as $fieldEn => $fieldAr) {
        if (!$fields->contains('field_en', $fieldEn)) {
            $newField = $category->fields()->create([
                'field_en' => $fieldEn,
                'field_ar' => $fieldAr,
            ]);
            $newField->setRelation('values', collect());
            $fields->push($newField);
        }
    }

    // ترتيب القيم داخل كل حقل مع استثناء القيم التي field_type == "text"
    $fields = $fields->map(function ($field) use ($sort) {
        $values = $field->values instanceof \Illuminate\Support\Collection
            ? $field->values
            : collect($field->values);

        // استثناء القيم التي نوعها text
        $values = $values->reject(function ($value) {
            return $value->field_type === 'text';
        });

        // ترتيب خاص لحقل "Model Year" بناءً على القيمة الرقمية من الأكبر للأصغر (الأحدث للأقدم)
        if ($field->field_en === 'Model Year') {
            $sortedValues = $values->sortByDesc(function ($value) use ($sort) {
                // القيمة تكون رقمية، نحولها لعدد صحيح للترتيب الرقمي الصحيح
                return (int) $value->{'value_' . $sort};
            })->values();
        } else {
            // ترتيب عادي أبجدي بناءً على لغة الفرز (ar أو en)
            $sortedValues = $sort === 'ar'
                ? $values->sortBy('value_ar')->values()
                : $values->sortBy('value_en')->values();
        }

        $field->setRelation('values', $sortedValues);

        return $field;
    });

    // ترتيب الحقول بحيث تكون الحقول المطلوبة في البداية
    $fields = $fields->sortBy(function ($field) use ($requiredFields) {
        return array_search($field->field_en, array_keys($requiredFields)) !== false
            ? array_search($field->field_en, array_keys($requiredFields))
            : 100;
    })->values();

    return response()->json([
        'success' => true,
        'data' => $fields,
    ]);
}

    


    public function store(Request $request, $categoryId)
    {
        $request->validate([
            'field_ar' => 'required|string',
            'field_en' => 'required|string',
            'values' => 'nullable|array',
            'values.*.value_ar' => 'required_with:values.*.value_en|string|nullable',
            'values.*.value_en' => 'required_with:values.*.value_ar|string|nullable',
        ]);

        $category = Category::findOrFail($categoryId);

        $field = new CategoryField();
        $field->category_id = $category->id;
        $field->field_ar = $request->field_ar;
        $field->field_en = $request->field_en;
        $field->save();

        if ($request->has('values')) {
            foreach ($request->values as $value) {
                // لو القيمتين فاضيين ما تضيف
                if (empty($value['value_ar']) && empty($value['value_en'])) {
                    continue;
                }

                CategoryFieldValue::create([
                    'category_field_id' => $field->id,
                    'value_ar' => $value['value_ar'],
                    'value_en' => $value['value_en'],
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الحقل بنجاح',
            'data' => $field
        ]);
    }


    // ✅ تحديث حقل معين وقيمه
    public function updateCategoryFieldArAndEn(Request $request, $fieldId)
    {
        $request->validate([
            'field_ar' => 'required|string',
            'field_en' => 'required|string',
        ]);
    
        $field = CategoryField::findOrFail($fieldId);
        $field->field_ar = $request->field_ar;
        $field->field_en = $request->field_en;
        $field->save();
    
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحقل بنجاح',
            'data' => $field
        ]);
    }
    

    // ✅ حذف حقل معين
    public function destroy($categoryId, $fieldId)
    {
        $field = CategoryField::where('category_id', $categoryId)->findOrFail($fieldId);
        $field->values()->delete();
        $field->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحقل بنجاح'
        ]);
    }

    public function storeCarModels(Request $request, $categoryId, $makeId)
    {
        $validator = \Validator::make($request->all(), [
            'make_ar' => 'required|array',
            'make_en' => 'required|array',
            'make_ar.*' => 'required|string',
            'make_en.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'ar' => $validator->errors()->toArray(),
                    'en' => $validator->errors()->toArray(),
                ]
            ], 422);
        }

        // بدون تحقق من الفئة أو الشركة المصنعة لتبسيط
        $insertData = [];

        foreach ($request->make_ar as $index => $arName) {
            $enName = $request->make_en[$index];

            $insertData[] = [
                'category_field_id' => $makeId, // تأكد فقط أن $makeId يشير إلى الـ category_field_value الصحيح
                'value_ar' => $arName,
                'value_en' => $enName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        \DB::table('car_models')->insert($insertData);

        return response()->json([
            'success' => true,
            'message' => [
                'message_ar' => 'تمت إضافة الموديلات بنجاح',
                'message_en' => 'Models added successfully',
            ],
        ]);
    }

    public function deleteCarModel($modelId)
    {
        $deleted = \DB::table('car_models')->where('id', $modelId)->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => [
                    'ar' => 'تم حذف الموديل بنجاح',
                    'en' => 'Model deleted successfully',
                ],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'errors' => [
                    'ar' => 'الموديل غير موجود',
                    'en' => 'Model not found',
                ],
            ], 404);
        }
    }


    public function storeMake(Request $request, $categoryId)
    {
        $validator = \Validator::make($request->all(), [
            'make_ar' => 'required|string',
            'make_en' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // نضيف الشركة المصنعة كحقل جديد في category_fields
        $categoryField = CategoryField::create([
            'category_id' => $categoryId,
            'field_ar' => $request->make_ar,
            'field_en' => $request->make_en,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الشركة المصنعة بنجاح',
            'data' => $categoryField,
        ]);
    }
}
