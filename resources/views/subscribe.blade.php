@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Subscribe') }}</div>

                <div class="card-body">
                    <form action="{{ route('subscribe.store') }}" method="POST" id="paymentForm">
                        @csrf
                        <div class="row">
                            

                            <div class="col-auto">
                                <label>Select the desired plan</label>
                                
                                    @foreach ($plans as $plan)
                                        <label class=" btn-outline-info rounded m-2 p-3" for="{{ $plan->slug }}">
                                            <input type="radio" name="plan" id="{{ $plan->slug }}" value="{{ $plan->slug }}" required>
                                            <p class="h2 font-weight-bold text-capitalize">{{ $plan->slug }}</p>
                                            <p class="display-4 text-capitalize">{{ $plan->visual_price }}</p>
                                        </label>
                                    @endforeach
                                
                            </div>


                        </div>

                        <div class="row">
                            

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
                            <button type="submit" id="payButton" class="btn btn-primary btn-lg">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
