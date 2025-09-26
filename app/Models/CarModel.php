<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    // Define the table this model is associated with
    protected $table = 'car_models';

    // Define the fields that are mass-assignable
    protected $fillable = ['category_field_id', 'value_ar', 'value_en'];

    /**
     * Define the relationship with the CategoryField model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function field()
    {
        return $this->belongsTo(CategoryField::class, 'category_field_id'); // Ensure the foreign key matches
    }

    /**
     * Optionally, if you need to get the formatted values or any other logic, you can add methods here.
     */

    // For example, if you want to get a readable value for the car model
    public function getFormattedValueAttribute()
    {
        return $this->value_en ?? $this->value_ar;
    }
}
