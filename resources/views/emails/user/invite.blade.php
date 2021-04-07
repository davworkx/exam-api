@component('mail::message')
# Signup Invitation

Click the button to signup.

@component('mail::button', ['url' => '/api/signup'])
Signup
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
