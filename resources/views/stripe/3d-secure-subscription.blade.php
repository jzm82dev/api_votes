@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Complete the security steps') }}</div>

                <div class="card-body">
                    <p>You need to follow some steps with your bank to complete this payment. Let's do it</p>
                    <!--  <p>Client Secret: {{ $clientSecret }}</p> -->
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        const stripe = Stripe('{{ config('services.stripe.key') }}');


        stripe.confirmCardPayment("{{ $clientSecret }}", {payment_method: "{{ $paymentMethod }}"}).
            then(function(result){
                if(result.error){
                    window.location.replace("{{ route('subscribe.cancelled') }}");
                }else{
                    window.location.replace("{!! route('subscribe.approval', [ 'plan' => $plan,  'subscriptionId' => $subscriptionId]) !!}");
                }
            })

    </script>
@endpush
@endsection
