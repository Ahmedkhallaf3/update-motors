<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorys = category::paginate(5);
        return view('pages.categorys.categorys', compact('categorys'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

 public function toggleKilometers($categoryId)
{
    $category = Category::findOrFail($categoryId);
    
    // نقلب القيمة
    $category->has_kilometers = !$category->has_kilometers;
    $category->save();

    $message = $category->has_kilometers 
        ? 'تم تفعيل حقل الكيلومترات.' 
        : 'تم تعطيل حقل الكيلومترات.';

    return redirect()->back()->with('message', $message);
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //$validated = $request->validated();
            $request->validate([
                'name_ar'        => 'required|string|',
                'name_en'        => 'required|string|',

            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',


            ]);

            $category = new category();

            $category->name_ar= $request->name_ar;
            $category->name_en= $request->name_en;

            $image=$request->image;
            $imagename=time().'_cat.webp';
            if (!is_dir(public_path('categorys'))) { mkdir(public_path('categorys'), 0755, true); }
            Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('categorys/'.$imagename));

            $category->image=$imagename;
            $category->save();
            //return $this->returnData('counter',$counter);
            $notification = array(
                'message' =>  trans('messages.success'),
                'alert-type' => 'success'
            );
            return redirect()->back()->with($notification);

        } catch (\Exception $e) {
           // return $this->returnError('E001','error');
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    
    


    /**
     * Display the specified resource.
     */


    /**
     * Show the form for editing the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            $request->validate([
                'name_ar'        => 'required|string|',
                'name_en'        => 'required|string|',

            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',


            ]);

            $categorys = category::findOrFail($request->id);
            $categorys->update([
                'name_ar'=> $request->name_ar,
                'name_en'=> $request->name_en,

            ]);

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                $oldImage = public_path('categorys/' . $categorys->image);
                if (File::exists($oldImage)) {
                    File::delete($oldImage);
                }

                // Upload the new image
                $image = $request->image;
                $imageName = time() . '_cat.webp';
                if (!is_dir(public_path('categorys'))) { mkdir(public_path('categorys'), 0755, true); }
                Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('categorys/'.$imageName));

                // Update the image name in the database
                $categorys->image = $imageName;
            }

            $categorys->save();

            $notification = array(
                'message' =>  trans('messages.success'),
                'alert-type' => 'success'
            );
           // return $this->returnData('counters',$counters);

            return redirect()->back()->with($notification);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
           // return $this->returnError('E001','error');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = category::findOrFail($id);

        // Delete the image from the folder
        $imagePath = public_path('categorys/' . $category->image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        // Delete the service record from the database
        $category->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
