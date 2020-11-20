@extends('admin.layouts.admin')
{{--

Вывод контента

--}}
@section('content')
    <div class="card">
        @include('admin.inc.belong_check')
        <div class="card-body">
            {{--

            Начало формы --}}
            @include('admin.inc.general_start')

            {!! $form::hidden('parent_id', $values->parent_id ?? $currentParent->id ?? null) !!}

            {!! $form::input('title', $values->title ?? null) !!}

            {!! $form::textarea('description', $values->description ?? null, null) !!}

            <div class="row">
                @isset($values->id)
                    <div class="col-xl-4 col-md-6">
                        {!! $form::select('status', config('add.page_statuses'), $values->status ?? null) !!}
                    </div>
                    <div class="col-xl-4 col-md-6">
                        {!! $form::input('sort', $values->sort ?? null, null) !!}
                    </div>
                @endisset

                <div class="col-xl-2 col-md-6">
                    {!! $form::input('price', $values->price ?? null, null) !!}
                </div>
                <div class="col-xl-2 col-md-6">
                    {!! $form::checkbox('default', $values->default ?? null) !!}
                </div>
            </div>

            {{--

            Конец формы --}}
            @include('admin.inc.general_end')
        </div>
    </div>
@endsection
