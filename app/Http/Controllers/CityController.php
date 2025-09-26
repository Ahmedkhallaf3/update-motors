<?php

namespace App\Http\Controllers;

use App\Models\city;
use App\Models\country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $citys = city::paginate(5);
        $countrys = country::all();
        return view('pages.citys.citys', compact('citys','countrys'));
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
                'country_id'        => 'required|exists:countries,id',

            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',


            ]);

            $city = new city();

            $city->name_ar= $request->name_ar;
            $city->name_en= $request->name_en;
            $city->country_id= $request->country_id;


            $city->save();
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
                'country_id'        => 'required|exists:countries,id',


            ],
            [
                'name_ar.required'        => 'The Arabic name is required.',
                'name_ar.string'          => 'The Arabic name must be a valid string.',

                'name_en.required'        => 'The English name is required.',
                'name_en.string'          => 'The English name must be a valid string.',


            ]);

            $citys = city::findOrFail($request->id);
            $citys->update([
                'name_ar'=> $request->name_ar,
                'name_en'=> $request->name_en,
                'country_id'=> $request->country_id,

            ]);



                // Upload the new image

            $citys->save();

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
        $city = city::findOrFail($id);

        // Delete the image from the folder


        // Delete the service record from the database
        $city->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
