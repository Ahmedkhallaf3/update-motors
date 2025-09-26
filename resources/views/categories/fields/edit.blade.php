@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h2>تعديل الحقل</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('categories.fields.update', [$category->id, $field->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="_method" value="POST">

        <div class="mb-3">
            <label>اسم الحقل بالعربي</label>
            <input type="text" name="field_ar" class="form-control" value="{{ $field->field_ar }}" required>
        </div>

        <div class="mb-3">
            <label>اسم الحقل بالإنجليزي</label>
            <input type="text" name="field_en" class="form-control" value="{{ $field->field_en }}" required>
        </div>

        <h4>القيم:</h4>
        <div id="values-container">
      @foreach($field->values as $index => $value)
    <div class="value-group mb-2">
        <input type="hidden" name="value_ids[]" value="{{ $value->id }}">
        <input type="text" name="values_ar[]" class="form-control mb-2" value="{{ $value->value_ar }}" required placeholder="القيمة بالعربي">
        <input type="text" name="values_en[]" class="form-control mb-2" value="{{ $value->value_en }}" required placeholder="القيمة بالإنجليزي">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeValue(this)">حذف</button>
    </div>
@endforeach

        </div>

        <button type="button" class="btn btn-secondary" onclick="addValue()">إضافة قيمة جديدة</button>

        <br><br>
        <button type="submit" class="btn btn-success">حفظ التعديلات</button>
    </form>
</div>

<script>
    function addValue() {
        let container = document.getElementById('values-container');
        let div = document.createElement('div');
        div.className = 'value-group mb-2';
        div.innerHTML = `
            <input type="text" name="values_ar[]" class="form-control mb-2" required placeholder="القيمة بالعربي">
            <input type="text" name="values_en[]" class="form-control mb-2" required placeholder="القيمة بالإنجليزي">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeValue(this)">حذف</button>
        `;
        container.appendChild(div);
    }
 function removeValue(button) {
    // العثور على العنصر الأب الذي يحتوي على المدخلات
    var parentElement = button.parentElement;

    // الحصول على القيم من الحقول المدخلة
    var valueAr = parentElement.querySelector('input[name="values_ar[]"]').value;
    var valueEn = parentElement.querySelector('input[name="values_en[]"]').value;
    var valueId = parentElement.querySelector('input[name="value_ids[]"]').value;

    // طباعة القيم في الكونسول
  //  console.log("القيمة بالعربي: " + valueAr);
   // console.log("القيمة بالإنجليزي: " + valueEn);
   // console.log("معرف القيمة: " + valueId);

    // محاولة الحصول على CSRF Token من meta أو input
    var csrfToken = null;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        csrfToken = csrfMeta.getAttribute('content');
    } else {
        var csrfInput = document.querySelector('input[name="_token"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
        }
    }

    if (!csrfToken) {
        console.error("⚠️ CSRF Token غير موجود!");
        return;
    }

    // طباعة الـ CSRF Token فقط
    console.log("CSRF Token: " + csrfToken);

    // التوقف هنا حسب طلبك


fetch(`/category-field-values/${valueId}/delete`, {
    method: 'DELETE',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        id: valueId
    })
})
.then(response => response.text())  // تحويل الاستجابة إلى نص
.then(data => {
    console.log("Response Text:", data);  // طباعة الاستجابة الفعلية
    try {
        let jsonResponse = JSON.parse(data); // حاول تحليل الاستجابة كـ JSON
        if (jsonResponse.success) {
            console.log("تم حذف القيمة بنجاح.");
        } else {
            console.log("حدث خطأ أثناء الحذف.");
        }
    } catch (error) {
        console.log("استجابة غير صالحة:", error);
    }
})
.catch(error => {
    console.log("خطأ في الاتصال بالخادم:", error);
});
// حذف العنصر من الصفحة
    parentElement.remove();


}



</script>
@endsection
