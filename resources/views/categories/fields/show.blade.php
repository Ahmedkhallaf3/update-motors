@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h2>{{ App::getLocale() == 'ar' ? 'حقول الفئة: ' . $category->name_ar : 'Category Fields: ' . $category->name_en }}</h2>

<a href="{{ route('categories.fields.create', $category->id) }}" class="btn btn-primary mb-3">
    {{ App::getLocale() == 'ar' ? 'إضافة حقل جديد' : 'Add New Field' }}
</a>

<div>
    <!-- عرض الحالة الحالية مع الأيقونة -->
    <p>
        <i class="fas {{ $category->has_kilometers ? 'fa-check-circle' : 'fa-times-circle' }}" style="color: {{ $category->has_kilometers ? 'green' : 'red' }};"></i>
        {{ App::getLocale() == 'ar' ? 'حالة حقل الكيلومترات: ' : 'Kilometer Field Status: ' }}
        {{ $category->has_kilometers ? (App::getLocale() == 'ar' ? 'مفعّل' : 'Enabled') : (App::getLocale() == 'ar' ? 'معطّل' : 'Disabled') }}
    </p>

    <!-- الزر لتغيير الحالة -->
    <form action="{{ route('categories.toggleKilometers', $category->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">
            {{ $category->has_kilometers ? (App::getLocale() == 'ar' ? 'إيقاف حقل الكيلومترات' : 'Disable Kilometer Field') : (App::getLocale() == 'ar' ? 'تفعيل حقل الكيلومترات' : 'Enable Kilometer Field') }}
        </button>
    </form>
</div>

 
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>اسم الحقل</th>
                <th>القيم</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @php
                $fixedFields = [
                    'Model Year' => 'سنة الموديل',
                    'Make' => 'الشركة المصنعة',
                ];
            @endphp

            @foreach($fixedFields as $field_en => $field_ar)
                @php
                    $field = $category->fields->where('field_en', $field_en)->first();
                @endphp
                <tr>
                    <td style="{{ $field_en == 'Make' ? 'color: black;' : '' }}">
                        {{ App::getLocale() == 'ar' ? $field_ar : $field_en }}
                    </td>
                    <td style="{{ $field_en == 'Make' ? 'color: black;' : '' }}">
    @if($field && $field->values->count())
        <!-- فورم لإضافة موديل جديد تحت الحقل "Make" -->
        @if($field_en == 'Make')
        <form action="{{ route('categories.fields.store-car-model', $category->id) }}" method="POST" id="makeForm">
    @csrf
    <div class="form-group">
        <label for="make">{{ __('اختر الشركة المصنعة') }}</label>
        <select name="make_id" id="make" class="form-control" required>
            <option value="">{{ __('اختر الشركة') }}</option>
            @foreach($field->values as $value)
                <option value="{{ $value->id }}">
                    {{ App::getLocale() == 'ar' ? $value->value_ar : $value->value_en }}
                </option>
            @endforeach
        </select>
    </div>

    <div id="modelFieldsContainer">
        <div class="modelField" id="modelField1">
            <label for="make_ar[]">{{ __('موديل 1') }}</label>
            <input type="text" class="form-control form-control-sm mt-2" placeholder="{{ __('المودل عربي') }}" name="make_ar[]" required />
            <input type="text" class="form-control form-control-sm mt-2" placeholder="{{ __('المودل انجليزي') }}" name="make_en[]" required />
        </div>
    </div>

    <button type="button" class="btn btn-secondary mt-2" onclick="addModelField()">{{ __('إضافة موديل') }}</button>
    <button type="submit" class="btn btn-primary mt-2">{{ __('حفظ') }}</button>
</form>

<!-- Loading Spinner (Initially Hidden) -->
<div id="loadingSpinner" style="display:none;">
    <img src="https://cdnjs.cloudflare.com/ajax/libs/Font-Awesome/4.7.0/fonts/fontawesome-webfont.svg" alt="Loading..." />
</div>

<script>
    let modelCount = 1; // العدّاد لتسلسل الموديلات

    // دالة لإضافة حقل موديل جديد
    function addModelField() {
        modelCount++; // زيادة العدّاد عند إضافة موديل جديد

        // إنشاء حقل جديد للموديل مع العنوان التسلسلي
        const modelFieldHTML = `
            <div class="modelField" id="modelField${modelCount}">
                <label for="make_ar[]">{{ __('موديل ') }}${modelCount}</label>
                <input type="text" class="form-control form-control-sm mt-2" placeholder="{{ __('المودل عربي') }}" name="make_ar[]" required />
                <input type="text" class="form-control form-control-sm mt-2" placeholder="{{ __('المودل انجليزي') }}" name="make_en[]" required />
            </div>
        `;

        // إضافة الحقل الجديد إلى حاوية الحقول
        document.getElementById('modelFieldsContainer').insertAdjacentHTML('beforeend', modelFieldHTML);
    }

    // منع ارسال الفورم بالطريقة التقليدية لإظهار مؤشر التحميل إذا كان هناك حاجة
    document.getElementById('makeForm').addEventListener('submit', function (event) {
        // إظهار مؤشر التحميل
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>

<style>
    /* Basic styling for the loading spinner */
    #loadingSpinner {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        display: none;
    }

    /* Style for each model field */
    .modelField {
        margin-bottom: 15px;
    }
</style>


        @endif

        <ul>
    @foreach($field->values as $value)
    <li style="{{ $field->field_en == 'Make' ? 'color: black;' : '' }}">
    <p>{{ App::getLocale() == 'ar' ? $value->value_ar : $value->value_en }}</p>

    @if($field->field_en == 'Make' && $value->carModels->count())
        <ul>
            @foreach($value->carModels as $model)
            <li class="d-flex align-items-center">
    <span>
        {{ App::getLocale() == 'ar' ? $model->value_ar : $model->value_en }}
    </span>

    <!-- Delete button with X icon -->
    <form action="{{ route('carModel.delete', $model->id) }}" method="POST" style="display: inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger p-1 ml-2" title="Delete" style="border-radius: 50%; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-times" style="font-size: 16px;"></i> <!-- Font Awesome X icon -->
    </button>
</form>

</li>

            @endforeach
        </ul>
    @endif
</li>

    @endforeach
</ul>

    @else
        <span class="text-muted">{{ __('لم يتم إضافة قيم بعد') }}</span>
    @endif
</td>


                    <td>
                        <form action="{{ route('categories.fields.ensureExists', [$category->id]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="field_en" value="{{ $field_en }}">
                            <input type="hidden" name="field_ar" value="{{ $field_ar }}">
                            <button type="submit" class="btn btn-warning btn-sm">تعديل</button>
                        </form>
                    </td>
                </tr>
            @endforeach

            @foreach($category->fields as $field)
                @if(!in_array($field->field_en, array_keys($fixedFields)))
                    <tr>
                        <td>{{ App::getLocale() == 'ar' ? $field->field_ar : $field->field_en }}</td>
                        <td>
                            <ul>
                                @foreach($field->values as $value)
                                    <li>{{ App::getLocale() == 'ar' ? $value->value_ar : $value->value_en }}</li>
                                @endforeach
                            </ul>
                        </td>
                        <td>
                            <a href="{{ route('categories.fields.edit', [$category->id, $field->id]) }}" class="btn btn-warning btn-sm">تعديل</a>
                            <form action="{{ route('categories.fields.destroy', [$category->id, $field->id]) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
@endsection
