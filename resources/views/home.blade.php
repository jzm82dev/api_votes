@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Make a payment') }}</div>

                <div class="card-body">
                    
                    <form action="{{ route('pay') }}" method="POST" id="paymentForm">
                        @csrf

                        <div class="row">
                            <div class="col-auto">
                                <label>How much do yoy pay?</label>
                                <input type="number" name="value" min="5" step="0.01" 
                                    class="form-control" value="{{ mt_rand(500, 100000) / 100 }}" required>
                                <small class="form-text text-muted">
                                    Use values with up to two decimal positions, using doc "."
                                </small>
                            </div>
                            <div class="col-auto">
                                <label>Currency</label>
                                <select name="currency" id="currency" class="custom-select form-control" required>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->iso }}">
                                            {{ strtoupper($currency->iso) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <label>Select the desired payment platform</label>
                                
                                    @foreach ($paymentPlatforms as $platform)
                                        <label class=" btn-outline-secondary rounded m-2 p-1" for="{{ $platform->name }}"
                                            data-target="#{{ $platform->name }}Collapse" data-toggle="collapse">
                                            <input type="radio" name="payment_platform" id="{{ $platform->name }}" value="{{ $platform->id }}" required>
                                            <img class="img-thumbnail" src=" {{ asset($platform->image) }}">
                                        </label>
                                    @endforeach
                                
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col">
                                
                                <div class="form-group" id="toggler">
                                    
                                    @foreach ($paymentPlatforms as $platform)
                                        <div id="{{ $platform->name }}Collapse" class="collapse" data-parent="#toggler">
                                            @includeIf('components.' . strtolower($platform->name) . '-collapse' )
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-auto">
                                <p class="border-bottom border-primary rounded">
                                    @if(! optional(auth()->user())->hasActiveSubscription() )
                                        Would you like a discount every time? <a href="{{ route('subscribe.show') }}">Subscribe</a>
                                    @else
                                        You get a <span class="font-weight-bold"> 10% off </span> as part of your subscription (will be applied in checkout)
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" id="payButton" class="btn btn-primary btn-lg">Pay</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
