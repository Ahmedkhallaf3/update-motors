<?php

namespace App\Http\Controllers;

use App\Models\blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = blog::paginate(5);
        return view('pages.blog.blog', compact('blogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            //$validated = $request->validated();
            $request->validate([
                'title_ar'        => 'required|string|',
                'title_en'        => 'required|string|',
                'description_ar'  => 'required|string',
                'description_en'  => 'required|string',
            ],
            [
                'title_ar.required'        => 'The Arabic title is required.',
                'title_ar.string'          => 'The Arabic title must be a valid string.',

                'title_en.required'        => 'The English title is required.',
                'title_en.string'          => 'The English title must be a valid string.',

                'description_ar.required'  => 'The Arabic description is required.',
                'description_ar.string'    => 'The Arabic description must be a valid string.',

                'description_en.required'  => 'The English description is required.',
                'description_en.string'    => 'The English description must be a valid string.',
            ]);

            $blog = new blog();

            $blog->title_ar= $request->title_ar;
            $blog->title_en= $request->title_en;
            $blog->description_ar = $request->description_ar;
            $blog->description_en = $request->description_en;

            $image=$request->image;
            $imagename=time().'_blog.webp';
            if (!is_dir(public_path('blog'))) { mkdir(public_path('blog'), 0755, true); }
            Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('blog/'.$imagename));

            $blog->image=$imagename;

            $blog->save();
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


    public function update(Request $request, blog $blog)
    {
        try {

            $request->validate([
                'title_ar'        => 'required|string|',
                'title_en'        => 'required|string|',
                'description_ar'  => 'required|string',
                'description_en'  => 'required|string',
            ],
            [
                'title_ar.required'        => 'The Arabic title is required.',
                'title_ar.string'          => 'The Arabic title must be a valid string.',

                'title_en.required'        => 'The English title is required.',
                'title_en.string'          => 'The English title must be a valid string.',

                'description_ar.required'  => 'The Arabic description is required.',
                'description_ar.string'    => 'The Arabic description must be a valid string.',

                'description_en.required'  => 'The English description is required.',
                'description_en.string'    => 'The English description must be a valid string.',
            ]);

            $blogs = blog::findOrFail($request->id);
            $blogs->update([
                'title_ar'=> $request->title_ar,
                'title_en'=> $request->title_en,
                'description_ar'=> $request->description_ar,
                'description_en'=> $request->description_en,
            ]);

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                $oldImage = public_path('blog/' . $blogs->image);
                if (File::exists($oldImage)) {
                    File::delete($oldImage);
                }

                // Upload the new image
                $image = $request->image;
                $imageName = time() . '_blog.webp';
                if (!is_dir(public_path('blog'))) { mkdir(public_path('blog'), 0755, true); }
                Image::make($image->getRealPath())->encode('webp', 85)->save(public_path('blog/'.$imageName));

                // Update the image name in the database
                $blogs->image = $imageName;
            }

            $blogs->save();


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
        $blog = blog::findOrFail($id);

        // Delete the image from the folder
        $imagePath = public_path('blog/' . $blog->image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        // Delete the service record from the database
        $blog->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
