<?php

namespace App\Http\Controllers;

use App\Models\country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countrys = country::paginate(5);
        return view('pages.countrys.countrys', compact('countrys'));
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
                'name_ar'        => 'required|string|',
                'name_en'        => 'required|string|',
               'currency_ar'        => 'required|string|',
                'currency_en'        => 'required|string|',

            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',
              
               'currency_ar.required'        => 'The Arabic currency_ar is required.',
                'currency_ar.string'          => 'The Arabic currency_ar must be a valid string.',

                'currency_en.required'        => 'The English currency_en is required.',
                'currency_en.string'          => 'The English currency_en must be a valid string.',


            ]);

            $country = new country();

            $country->name_ar= $request->name_ar;
            $country->name_en= $request->name_en;
          
          $country->currency_ar= $request->currency_ar;
            $country->currency_en= $request->currency_en;

            $image=$request->image;
            $imagename=time().'.'.$image->getClientOriginalExtension();
            $request->image->move(public_path('countrys'), $imagename);

            $country->image=$imagename;
            $country->save();
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
               'currency_ar'        => 'required|string|',
                'currency_en'        => 'required|string|',

            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',
              
               'currency_ar.required'        => 'The Arabic currency_ar is required.',
                'currency_ar.string'          => 'The Arabic currency_ar must be a valid string.',

                'currency_en.required'        => 'The English currency_en is required.',
                'currency_en.string'          => 'The English currency_en must be a valid string.',


            ]);

            $countrys = country::findOrFail($request->id);
            $countrys->update([
                'name_ar'=> $request->name_ar,
                'name_en'=> $request->name_en,
              
              'currency_ar'=> $request->currency_ar,
                'currency_en'=> $request->currency_en,

            ]);

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                $oldImage = public_path('countrys/' . $countrys->image);
                if (File::exists($oldImage)) {
                    File::delete($oldImage);
                }

                // Upload the new image
                $image = $request->image;
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('countrys'), $imageName);

                // Update the image name in the database
                $countrys->image = $imageName;
            }

            $countrys->save();

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
        $country = country::findOrFail($id);

        // Delete the image from the folder
        $imagePath = public_path('countrys/' . $country->image);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }

        // Delete the service record from the database
        $country->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
