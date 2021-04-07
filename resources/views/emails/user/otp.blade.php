@component('mail::message')
# Please verify your account

<br>

OTP: {{$data['otp']}}

<br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent
