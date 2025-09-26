<?php

namespace App\Http\Controllers;

use App\Models\banner;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = banner::paginate(5);
        return view('pages.banners.banners', compact('banners'));
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
        $request->validate([
            'image_ar' => 'required|image',
            'image_en' => 'required|image',
        ]);

        $banner = new Banner();

        if ($request->hasFile('image_ar')) {
            $image_ar = $request->file('image_ar');
            $imagename_ar = time() . '_ar.webp';
            if (!is_dir(public_path('image_ar'))) { mkdir(public_path('image_ar'), 0755, true); }
            Image::make($image_ar->getRealPath())->encode('webp', 85)->save(public_path('image_ar/' . $imagename_ar));
            $banner->image_ar = $imagename_ar;
        }

        if ($request->hasFile('image_en')) {
            $image_en = $request->file('image_en');
            $imagename_en = time() . '_en.webp';
            if (!is_dir(public_path('image_en'))) { mkdir(public_path('image_en'), 0755, true); }
            Image::make($image_en->getRealPath())->encode('webp', 85)->save(public_path('image_en/' . $imagename_en));
            $banner->image_en = $imagename_en;
        }

        $banner->save();

        return redirect()->back()->with('success', 'تمت إضافة الصورة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(banner $banner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */


public function update(Request $request, $id)
{
    $banner = Banner::findOrFail($id);

    $request->validate([
        'image_ar' => 'nullable|image|max:2048',
        'image_en' => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('image_ar')) {
        // حذف الصورة القديمة
        if ($banner->image_ar && file_exists(public_path('image_ar/' . $banner->image_ar))) {
            unlink(public_path('image_ar/' . $banner->image_ar));
        }

        $image_ar = $request->file('image_ar');
        $imagename_ar = time() . '_ar.webp';
        if (!is_dir(public_path('image_ar'))) { mkdir(public_path('image_ar'), 0755, true); }
        Image::make($image_ar->getRealPath())->encode('webp', 85)->save(public_path('image_ar/' . $imagename_ar));
        $banner->image_ar = $imagename_ar;
    }

    if ($request->hasFile('image_en')) {
        // حذف الصورة القديمة
        if ($banner->image_en && file_exists(public_path('image_en/' . $banner->image_en))) {
            unlink(public_path('image_en/' . $banner->image_en));
        }

        $image_en = $request->file('image_en');
        $imagename_en = time() . '_en.webp';
        if (!is_dir(public_path('image_en'))) { mkdir(public_path('image_en'), 0755, true); }
        Image::make($image_en->getRealPath())->encode('webp', 85)->save(public_path('image_en/' . $imagename_en));
        $banner->image_en = $imagename_en;
    }

    $banner->save();

    return redirect()->back()->with('success', 'تم تحديث الصورة بنجاح');
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
{
    $banner = Banner::findOrFail($id);

    // حذف الصور من السيرفر
    if (file_exists(public_path('image_ar/' . $banner->image_ar))) {
        unlink(public_path('image_ar/' . $banner->image_ar));
    }

    if (file_exists(public_path('image_en/' . $banner->image_en))) {
        unlink(public_path('image_en/' . $banner->image_en));
    }

    // حذف السجل من قاعدة البيانات
    $banner->delete();

    return redirect()->back()->with('success', 'تم حذف الصورة بنجاح');
}

}
