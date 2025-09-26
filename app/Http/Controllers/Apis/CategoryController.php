<?php

namespace App\Http\Controllers\Apis;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Models\CategoryFieldValue;
use App\Models\CategoryField;
use App\Http\Controllers\VisitorLogController;  // استدعاء الكنترولر
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    /**
     * عرض جميع التصنيفات مع روابط الصور.
     */


public function index()
{
    $customOrder = [
        1 => 'Cars',
        3 => 'Classic Cars',
        10 => 'Number Plates',
        5 => 'Bikes',
        7 => 'Jet Ski',
        8 => 'Marine Engine',
        2 => 'Boot & Yacht',
        9 => 'Heavy Machinery',
        13 => 'Rent a Car',
        14 => 'Taxi on Apps',
        11 => 'Spare Parts',
        12 => 'Accessories',
        15 => 'Service and Repair',
        6 => 'Trailers',
        16 => 'Scrap',
    ];

    $orderedIds = array_keys($customOrder);

    // Get categories in the custom order
    $orderedCategories = Category::whereIn('id', $orderedIds)
        ->orderByRaw('FIELD(id, ' . implode(',', $orderedIds) . ')')
        ->get();

    // Get categories not in the custom order
    $remainingCategories = Category::whereNotIn('id', $orderedIds)
        ->orderBy('id', 'asc')
        ->get();

    // Merge both collections
    $categories = $orderedCategories->concat($remainingCategories);

    // استدعاء الكنترولر الخاص بالإحصائيات
    $visitorLogController = new VisitorLogController();

    // نحضر الإحصائيات لكل تصنيف
    $categories->transform(function ($category) use ($visitorLogController) {
        // استدعاء دالة statistics مع تمرير category_id
        $request = request()->merge(['category_id' => $category->id]); // تحضير request وهمي مع category_id

        $response = $visitorLogController->statistics($request);

        // جلب محتوى ال json من Response
        $stats = json_decode($response->getContent(), true);

        // إضافة الإحصائيات لل category
        $category->statistics = $stats;

        // تعديل رابط الصورة
        $category->image = url('categorys/' . $category->image);

        return $category;
    });

    return response()->json([
        'categories' => $categories
    ], 200);
}


    /**
     * إضافة تصنيف جديد.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'image'   => 'required|image',
        ]);

        $category = new Category();
        $category->name_ar = $request->name_ar;
        $category->name_en = $request->name_en;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_cat.webp';
            if (!is_dir(public_path('categorys'))) { mkdir(public_path('categorys'), 0755, true); }
            Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('categorys/' . $imageName));
            $category->image = $imageName;
        }

        $category->save();

        return response()->json([
            'message' => 'تمت إضافة التصنيف بنجاح',
            'category' => [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'image' => url('categorys/' . $category->image)
            ]
        ], 201);
    }

    /**
     * تحديث تصنيف موجود.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'image'   => 'nullable|image',
        ]);

        $category->name_ar = $request->name_ar;
        $category->name_en = $request->name_en;

        if ($request->hasFile('image')) {
            $oldImage = public_path('categorys/' . $category->image);
            if (File::exists($oldImage)) {
                File::delete($oldImage);
            }

            $image = $request->file('image');
            $imageName = time() . '_cat.webp';
            if (!is_dir(public_path('categorys'))) { mkdir(public_path('categorys'), 0755, true); }
            Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('categorys/' . $imageName));
            $category->image = $imageName;
        }

        $category->save();

        return response()->json([
            'message' => 'تم تحديث التصنيف بنجاح',
            'category' => [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'image' => url('categorys/' . $category->image)
            ]
        ], 200);
    }

    /**
     * حذف تصنيف.
     */
    public function destroy($id)
    {
        $category = category::findOrFail($id);

        $imagePath = public_path('categorys/' . $category->image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        $category->delete();

        return response()->json([
            'message' => 'تم حذف التصنيف بنجاح'
        ], 200);
    }

    public function toggleKilometersApi($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->has_kilometers = !$category->has_kilometers;
        $category->save();

        return response()->json([
            'message' => 'Kilometer field status updated successfully',
            'category_id' => $category->id,
            'has_kilometers' => $category->has_kilometers,
        ]);
    }

    public function listMakes($categoryId)
    {
        // جلب الفئة مع الحقول والقيم الخاصة بها (نفترض أن 'Make' هو اسم الحقل)
        $category = Category::with(['fields.values'])->findOrFail($categoryId);

        // البحث عن حقل 'Make'
        $makeField = $category->fields->firstWhere('field_en', 'Make');

        if (!$makeField) {
            return response()->json([
                'success' => false,
                'message' => 'Make field not found for this category.'
            ], 404);
        }

        // جلب القيم الخاصة بحقل الـ Make (الشركات المصنعة)
        $makes = $makeField->values->map(function ($value) {
            return [
                'id' => $value->id,
                'name_ar' => $value->value_ar,
                'name_en' => $value->value_en,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $makes
        ]);
    }

    public function addNewMake(Request $request, $categoryId)
    {
        // التحقق من وجود البيانات المطلوبة في الطلب
        $request->validate([
            'field_id' => 'required|integer',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        // جلب الفئة مع الحقول الخاصة بها
        $category = Category::with(['fields'])->findOrFail($categoryId);

        // جلب الحقل المطلوب من الفئة
        $field = $category->fields->where('id', $request->input('field_id'))->first();

        if (!$field) {
            return response()->json([
                'success' => false,
                'msg_ar' => 'الحقل غير موجود لهذه الفئة.',
                'msg_en' => 'Field not found for this category.'
            ], 404);
        }

        // إضافة قيمة جديدة للحقل
        $newValue = $field->values()->create([
            'value_ar' => $request->input('name_ar'),
            'value_en' => $request->input('name_en'),
        ]);

        return response()->json([
            'success' => true,
            'message_ar' => 'تمت إضافة القيمة الجديدة بنجاح.',
            'message_en' => 'New value added successfully.',
            'data' => [
                'id' => $newValue->id,
                'name_ar' => $newValue->value_ar,
                'name_en' => $newValue->value_en,
                'category_id' => $categoryId,
                'category_name_en' => $category->name_en ?? null,
                'category_name_ar' => $category->name_ar ?? null,
                'field_id' => $field->id,
                'field_name_en' => $field->field_en ?? null,
                'field_name_ar' => $field->field_ar ?? null,
                'created_at' => $newValue->created_at->toDateTimeString(),
            ]
        ]);
    }



    public function editMakeById(Request $request, $valId)
    {
        // Validate input
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        // Find the value by ID
        $fieldValue = CategoryFieldValue::find($valId);

        if (!$fieldValue) {
            return response()->json([
                'success' => false,
                'msg_ar' => 'القيمة غير موجودة.',
                'msg_en' => 'Value not found.'
            ], 404);
        }

        // Update the value
        $fieldValue->update([
            'value_ar' => $request->input('name_ar'),
            'value_en' => $request->input('name_en'),
        ]);

        // Get related field and category for response (if relations exist)
        $field = $fieldValue->field ?? null;
        $category = $field ? $field->category : null;

        return response()->json([
            'success' => true,
            'msg_ar' => 'تم تعديل القيمة بنجاح.',
            'msg_en' => 'Value updated successfully.',
            'data' => [
                'id' => $fieldValue->id,
                'name_ar' => $fieldValue->value_ar,
                'name_en' => $fieldValue->value_en,
                'category_id' => $field->category_id ?? null,
                'category_name_en' => $category->name_en ?? null,
                'category_name_ar' => $category->name_ar ?? null,
                'field_id' => $field->id ?? null,
                'field_name_en' => $field->field_en ?? null,
                'field_name_ar' => $field->field_ar ?? null,
                'updated_at' => $fieldValue->updated_at->toDateTimeString(),
            ]
        ]);
    }


    public function deleteMakeById($catId, $fldId, $valId)
    {
        // جلب الفئة مع الحقول
        $category = Category::with(['fields'])->findOrFail($catId);

        // جلب الحقل والتأكد أنه تابع للفئة
        $field = $category->fields->where('id', $fldId)->first();

        if (!$field) {
            return response()->json([
                'success' => false,
                'msg_ar' => 'الحقل غير موجود لهذه الفئة.',
                'msg_en' => 'Field not found for this category.'
            ], 404);
        }

        // جلب القيمة والتأكد أنها تتبع الحقل
        $fieldValue = $field->values()->find($valId);

        if (!$fieldValue) {
            return response()->json([
                'success' => false,
                'msg_ar' => 'القيمة المطلوبة غير موجودة أو لا تتبع هذا الحقل.',
                'msg_en' => 'Value not found or does not belong to this field.'
            ], 404);
        }

        // حذف القيمة
        $fieldValue->delete();

        return response()->json([
            'success' => true,
            'msg_ar' => 'تم حذف القيمة بنجاح.',
            'msg_en' => 'Value deleted successfully.'
        ]);
    }

    public function addField(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'field_ar' => 'required|string|max:255',
            'field_en' => 'required|string|max:255',
        ]);
    
        $field = CategoryField::create([
            'category_id' => $request->input('category_id'),
            'field_ar' => $request->input('field_ar'),
            'field_en' => $request->input('field_en'),
            // no need to pass 'type' or 'is_required'
        ]);
    
        return response()->json([
            'success' => true,
            'msg_ar' => 'تمت إضافة الحقل بنجاح.',
            'msg_en' => 'Field added successfully.',
            'data' => $field
        ]);
    }
    

}
