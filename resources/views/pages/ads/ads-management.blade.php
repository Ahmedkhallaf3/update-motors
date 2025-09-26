@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">إدارة الإعلانات</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <label for="statusFilter" class="form-label">فلترة حسب الحالة:</label>
        <select id="statusFilter" class="form-select" onchange="filterAds()">
            <option value="">كل الإعلانات</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>مقبول</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
        </select>
    </div>

    <script>
        function filterAds() {
            let status = document.getElementById('statusFilter').value;
            window.location.href = "{{ route('ads.management') }}?status=" + status;
        }
    </script>


    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>عنوان الإعلان</th>
                <th>الوصف</th>
                              <th>العنوان</th>

                <th>السعر</th>
                <th>رقم الهاتف</th>
                <th>المستخدم</th>
                              <th>كيلو متر</th>

                <th>الحالة</th>
                <th>الصورة الرئيسية</th>
                <th>الصور الفرعية</th>
                <th>الحقول والقيم</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ads as $ad)
            <tr>
                <td>{{ $ad->id }}</td>
                <td>{{ $ad->title }}</td>
                <td>{{ Str::limit($ad->description, 50) }}</td>
              
                              <td>{{ $ad->address }}</td>

                <td>{{ $ad->price }}</td>
                <td>{{ $ad->phone_number }}</td>
                <td>{{ $ad->user->first_name ?? 'غير معروف' }} {{ $ad->user->last_name ?? 'غير معروف' }}</td>
                              <td>{{ $ad->kilometer }}</td>

                <td>
                    @if($ad->status == 'pending')
                        <span class="badge bg-warning">قيد المراجعة</span>
                    @elseif($ad->status == 'approved')
                        <span class="badge bg-success">مقبول</span>
                    @elseif($ad->status == 'rejected')
                        <span class="badge bg-danger">مرفوض</span>
                    @endif
                </td>
                <td>
                    @if($ad->main_image)
                        <img src="{{ asset('/' . $ad->main_image) }}" width="80">
                    @else
                        <span>لا يوجد صورة</span>
                    @endif
                </td>
                <td>
                    @foreach($ad->subImages as $image)
                        <img src="{{ asset('/' . $image->image) }}" width="60">
                    @endforeach
                </td>
               <td>
    <ul>
        @foreach($ad->fieldValues as $fieldValue)
            <li>
                @php
                    $locale = app()->getLocale();
$fieldName = $locale === 'ar' ? optional($fieldValue->field)->field_ar : optional($fieldValue->field)->field_en;
                    $fieldValueName = $locale === 'ar' ? optional($fieldValue->fieldValue)->value_ar : optional($fieldValue->fieldValue)->value_en;
                @endphp
                <strong>{{ $fieldName ?? 'غير معروف' }}:</strong> {{ $fieldValueName ?? 'غير معروف' }}
            </li>
        @endforeach
    </ul>
</td>

                <td>
                    <!-- زر الموافقة -->
                    <form action="{{ route('ads.updateStatus', $ad->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-success btn-sm">موافقة</button>
                    </form>

                    <!-- زر الرفض -->
                    <form action="{{ route('ads.updateStatus', $ad->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-danger btn-sm">رفض</button>
                    </form>

                    <!-- زر الحذف -->
                    <form action="{{ route('ads.destroy', $ad->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-warning btn-sm">حذف</button>
                    </form>
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
