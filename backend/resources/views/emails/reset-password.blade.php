<x-mail-layout
    title="Reset your OSEP password"
    heading="Reset your password"
    action="Choose a new password"
    :url="$url"
>
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#334155;">
        Hi {{ $user->first_name }}, we received a request to reset the password on your OSEP account.
    </p>
    <p style="margin:0 0 8px 0;font-size:15px;line-height:1.7;color:#334155;">
        This link expires in {{ $expiresInMinutes }} minutes. If you didn't ask for a reset, no action is
        needed &mdash; your password stays exactly as it is.
    </p>
</x-mail-layout>
