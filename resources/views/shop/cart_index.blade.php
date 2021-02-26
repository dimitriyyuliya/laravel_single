{{--

Наследуем шаблон --}}
@extends('layouts.default')
{{--

Подключается блок header --}}
@section('header')
    @include('inc.header')
@endsection
@section('content')
    <main class="main">
        <div class="container">
            <div class="row">
                <div class="col text-center mt-4">
                    <h1>{{ $title }}</h1>
                </div>
            </div>
            <div class="pt-2 pb-5 px-4">
                <div class="row">
                    <div class="col pt-4 no_js">
                        {{--


                        Корзина --}}
                        @include("{$viewPath}.cart_modal")
                        @if(session()->has('cart'))
                            {{--


                            Купон или акции --}}
                            {{--


                            Доставка --}}
                            @if($delivery = config('shop.delivery'))
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <form action="{{ route('delivery_post') }}" method="post" class="text-center change_submit">
                                            @csrf
                                            @php

                                                $deliverySave = session()->has('delivery.title');

                                            @endphp
                                            @foreach($delivery as $key => $item)
                                                @php

                                                    // Если сумма бесплатного лимита доставки больше суммы в корзине, то доставка бесплатная
                                                    if (isset($item['sum'])) {
                                                        $deliverySum = isset($item['free_after']) && session('cart.sum') < $item['free_after'] ? $item['sum'] : 0;
                                                    } else {
                                                        $deliverySum = 0;
                                                    }

                                                    $checked = $deliverySave ? session('delivery.title') === $item['title'] : !$key;


                                                @endphp
                                                {!! radioBtn('delivery', $item['title'], 'cart', false, $checked, null, __("s.{$item['title']}"), $item['icon'], ['data-add' => $deliverySum]) !!}
                                            @endforeach
                                        </form>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="table-responsive cart_block">
                                        <table class="table no-wrap a-black">
                                            <tbody>
                                            <tr>
                                                <td class="w-5">@lang('s.delivery'):</td>
                                                <td id="delivery_title">@lang('s.pickup')</td>
                                                <th class="text-right pr-4">
                                                    <span id="delivery_add">0</span>
                                                    <small>&#8381;</small>
                                                </th>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive cart_block">
                                        <table class="table no-wrap a-black">
                                            <tbody>
                                            <tr>
                                                <th>@lang('s.total'):</th>
                                                <th class="text-right pr-4">
                                                    <span id="all_sum">{{ (session()->has('cart.sum') ? session('cart.sum') : 0) + (session()->has('delivery.sum') ? session('delivery.sum') : null) }}</span>
                                                    <small>&#8381;</small>
                                                </th>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if(session()->has('cart.products'))
                    {{--


                    Форма заказа --}}
                    <div class="mt-3 mb-5 no-wrap">
                        <form method="post" action="{{ route('make_order') }}" name="order" class="validate form-dark" novalidate>
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    @csrf
                                    {!! hidden('g-recaptcha-response', '', 'data-id="g-recaptcha-response"') !!}
                                    {{--{!! hidden('address') !!}--}}
                                    {!! input('name', null, true, false, auth()->check() ? auth()->user()->name : null) !!}
                                    {!! input('tel', null, true, 'tel', auth()->check() ? auth()->user()->tel : null) !!}
                                    {!! input('email', null, true, false, auth()->check() ? auth()->user()->email : null) !!}
                                    @if(session()->has('delivery.delivery') && session('delivery.delivery'))
                                        {!! textarea('address', null, true, false, auth()->check() ? auth()->user()->address : null) !!}
                                        @if($addAddress = config('shop.add_address'))
                                            <div class="row">
                                                @foreach($addAddress as $item)
                                                    <div class="col-6">
                                                        {!! input($item, auth()->check() ? auth()->user()->$item : null, null) !!}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                    {!! textarea('message', null, null, null, null, __('s.comment')) !!}
                                    @if(!auth()->check())
                                        {!! checkboxSwitch('accept', null, true, true) !!}
                                    @endif
                                    {!! btn('submit') !!}
                                    {!! recaptchaText('a-black mt-4') !!}
                                </div>
                            </div>
                        </form>
                    </div>

                @else

                    <div class="text-center mb-3">
                        <a href="{{ route('catalog') }}" class="btn btn-primary pulse mt-4">@lang('s.catalog')</a>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection
{{--

Подключается блок footer

--}}
@section('footer')
    @include('inc.footer')
@endsection
{{--

Модальное окно предложение авторизации --}}
{!! modal('guest', __('s.continue_as'), 'modal-dark') !!}
<div class="row mt-5 mb-4 no-wrap">
    <div class="col-6">
        <a href="{{ route('login') }}" class="btn btn-primary">@lang('a.user')</a>
    </div>
    <div class="col-6 text-right">
        <button class="btn btn-outline-primary mr-2" data-dismiss="modal">@lang('a.guest')</button>
    </div>
</div>
{!! modalEnd() !!}

@section('js')
    @if(session()->has('cart.products'))
        <script>
            {{--

            Вызов модального окна предложение авторизации --}}
            if (!auth) {
                $('#guest').modal()
            }
        </script>
    @endif
@endsection
