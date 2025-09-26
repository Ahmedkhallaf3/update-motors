<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\CategoryField;
use App\Models\CarModel;
use App\Models\CategoryFieldValue;
class CategoryFieldController extends Controller
{
    public function ensureExists($categoryId, Request $request)
    {
        $category = Category::findOrFail($categoryId);

        // Check if the field already exists
        $field = $category->fields()->where('field_en', $request->field_en)->first();

        if (!$field) {
            // Create the field if not exists
            $field = $category->fields()->create([
                'field_en' => $request->field_en,
                'field_ar' => $request->field_ar
            ]);
        }

        // Redirect to the field edit page
        return redirect()->route('categories.fields.edit', [$category->id, $field->id]);
    }

    public function create($id)
    {
        $category = Category::findOrFail($id);
        return view('categories.fields.create', compact('category'));
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'fields' => 'required|array',
        ]);
    
        foreach ($request->fields as $field) {
            if (!isset($field['field_ar'], $field['field_en'])) {
                continue; // Ignore if the main fields are missing
            }

            // Create the main field in the category_fields table
            $categoryField = CategoryField::create([
                'category_id' => $id,
                'field_ar' => $field['field_ar'],
                'field_en' => $field['field_en'],
            ]);
    
            // If values exist, save them in the category_field_values table
            if (isset($field['values_ar']) && isset($field['values_en'])) {
                foreach ($field['values_ar'] as $index => $value_ar) {
                    $value_en = $field['values_en'][$index] ?? ''; // Default to empty if no English value
                    $categoryField->values()->create([
                        'value_ar' => $value_ar,
                        'value_en' => $value_en,
                    ]);
                }
            }
        }
    
        return redirect()->route('categories.fields.create', $id)->with('success', 'تمت إضافة الحقول والقيم بنجاح!');
    }

    public function show($id)
    {
        $category = Category::with(['fields.values.carModels'])->findOrFail($id);
        return view('categories.fields.show', compact('category'));
    }
    

    public function edit($id, $field_id)
    {
        $category = Category::findOrFail($id);
        $field = CategoryField::with('values')->findOrFail($field_id);
        return view('categories.fields.edit', compact('category', 'field'));
    }

     public function update(Request $request, $id, $field_id)
{
    $request->validate([
        'field_ar' => 'required|string',
        'field_en' => 'required|string',
        'values_ar' => 'required|array',
        'values_en' => 'required|array',
        'value_ids' => 'array',
    ]);

    $field = CategoryField::findOrFail($field_id);
    $field->update([
        'field_ar' => $request->field_ar,
        'field_en' => $request->field_en,
    ]);

    $submittedIds = $request->value_ids ?? [];
    $existingIds = $field->values()->pluck('id')->toArray();

    foreach ($request->values_ar as $index => $value_ar) {
        $value_en = $request->values_en[$index] ?? '';
        $value_id = $submittedIds[$index] ?? null;

        if ($value_id) {
            // تحديث قيمة موجودة
            $existingValue = $field->values()->where('id', $value_id)->first();
            if ($existingValue) {
                $existingValue->update([
                    'value_ar' => $value_ar,
                    'value_en' => $value_en,
                ]);
            }
        } else {
            // إضافة قيمة جديدة
            $field->values()->create([
                'value_ar' => $value_ar,
                'value_en' => $value_en,
            ]);
        }
    }

    return redirect()->route('categories.fields.show', $id)->with('success', 'تم تحديث الحقل بنجاح!');
}

public function deleteValue($valueId)
{
    try {
        // تأكد من أن الكلاس مستورد بشكل صحيح
        $value = CategoryFieldValue::findOrFail($valueId);
        $value->delete();
        return response()->json(['success' => true], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}




    public function destroy($id, $field_id)
    {
        $field = CategoryField::findOrFail($field_id);
        $field->delete();

        return redirect()->route('categories.fields.show', $id)->with('success', 'تم حذف الحقل بنجاح!');
    }

    public function storeCarModel(Request $request, $categoryId)
{
    try {
        // Validate the data
        $request->validate([
            'make_id' => 'required|exists:category_field_values,id', // Ensure the selected company exists
            'make_ar.*' => 'required|string', // Validate each Arabic model name
            'make_en.*' => 'required|string', // Validate each English model name
        ], [
            'make_ar.*.required' => 'اسم الموديل بالعربية مطلوب',
            'make_en.*.required' => 'اسم الموديل بالإنجليزية مطلوب',
        ]);

        // Add the new car models and link them to the selected make (company)
        foreach ($request->make_ar as $index => $make_ar) {
            CarModel::create([
                'category_field_id' => $request->make_id, // Use the selected company (correct!)
                'value_ar' => $make_ar,
                'value_en' => $request->make_en[$index],
            ]);
        }

        // Redirect with success message
        return redirect()->back()->with('success', 'تم حفظ الموديلات بنجاح');
    } catch (\Exception $e) {
        // Handle any errors
        return redirect()->back()->withErrors(['error' => $e->getMessage()]);
    }
}

}
