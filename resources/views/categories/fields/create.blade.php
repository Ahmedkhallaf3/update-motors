@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h2>إضافة حقول للفئة: @if(App::getLocale() == 'ar')
        {{ $category->name_ar }}
    @else
        {{ $category->name_en }}
    @endif</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('categories.fields.store', $category->id) }}" method="POST">
        @csrf

        <div id="fields-container">
            <h4>إضافة الحقول</h4>
        </div>

        <button type="button" class="btn btn-primary" onclick="addField()">إضافة حقل جديد</button>
        <br><br>

        <button type="submit" class="btn btn-success">حفظ</button>
    </form>
</div>

<script>
    function addField() {
        let container = document.getElementById('fields-container');
        let div = document.createElement('div');
        div.className = 'field-group border p-3 mb-3';

        let fieldIndex = document.getElementsByClassName('field-group').length; // لحفظ ترتيب الحقول

        div.innerHTML = `
            <h5>الحقل ${fieldIndex + 1}</h5>
            <input type="text" name="fields[${fieldIndex}][field_ar]" class="form-control mb-2" placeholder="اسم الحقل بالعربي" required>
            <input type="text" name="fields[${fieldIndex}][field_en]" class="form-control mb-2" placeholder="اسم الحقل بالإنجليزي" required>

            <div id="values-container-${fieldIndex}">
                <h6>القيم</h6>
            </div>

            <button type="button" class="btn btn-secondary" onclick="addValue(${fieldIndex})">إضافة قيمة جديدة</button>
            <button type="button" class="btn btn-danger" onclick="removeField(this)">حذف الحقل</button>
            <hr>
        `;
        container.appendChild(div);
    }

    function addValue(fieldIndex) {
        let container = document.getElementById(`values-container-${fieldIndex}`);
        let valueIndex = container.getElementsByClassName('value-group').length;

        let div = document.createElement('div');
        div.className = 'value-group mb-2';
        div.innerHTML = `
            <input type="text" name="fields[${fieldIndex}][values_ar][]" class="form-control mb-2" placeholder="القيمة بالعربي" required>
            <input type="text" name="fields[${fieldIndex}][values_en][]" class="form-control mb-2" placeholder="القيمة بالإنجليزي" required>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeValue(this)">حذف القيمة</button>
        `;
        container.appendChild(div);
    }

    function removeField(button) {
        button.parentElement.remove();
    }

    function removeValue(button) {
        button.parentElement.remove();
    }
</script>
@endsection
