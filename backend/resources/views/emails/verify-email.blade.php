<x-mail-layout
    title="Confirm your OSEP account"
    heading="Welcome to OSEP, {{ $user->first_name }}."
    action="Confirm my email"
    :url="$url"
>
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#334155;">
        Your {{ $user->account_type->label() }} account is almost ready. Confirm your email address to
        activate it and unlock your dashboard.
    </p>
    <p style="margin:0 0 8px 0;font-size:15px;line-height:1.7;color:#334155;">
        This link expires in {{ $expiresInMinutes }} minutes.
    </p>
</x-mail-layout>
