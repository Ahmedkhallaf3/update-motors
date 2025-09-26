<?php
namespace App\Http\Controllers;

use App\Models\CarModel;
use Illuminate\Http\Request;

class CarModelController extends Controller
{
    // Method to delete a car model
    public function destroy($id)
    {
        // Find the car model by ID
        $carModel = CarModel::findOrFail($id);

        // Delete the car model
        $carModel->delete();

        // Redirect back to the previous page with a success message
        return back()->with('success', 'Car model deleted successfully!');
    }

    public function getByMakeId($make_id)
    {
        $models = CarModel::where('category_field_id', $make_id)->get();
    
        return response()->json(['models' => $models]);
    }

}
