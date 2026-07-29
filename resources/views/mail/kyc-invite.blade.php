<x-mail::message>
# Complete your KYC

Hi {{ $user->name }},

Thanks for signing up. Before you can access the developer portal and API keys, please complete KYC verification.

Upload your company / identity documents using the secure link below (expires in 7 days).

<x-mail::button :url="$url">
Upload KYC documents
</x-mail::button>

After you submit, our team will review your application. You will be able to sign in once approved — UAT credentials are issued automatically on approval.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
