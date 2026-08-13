@props([
    'title' => config('app.name'),
    'heading' => '',
    'url' => '#',
    'action' => 'Continue',
])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:'Plus Jakarta Sans','Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0F172A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC;padding:40px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background-color:#FFFFFF;border:1px solid #E2E8F0;border-radius:20px;overflow:hidden;">

                <tr>
                    <td style="background-color:#1E3A8A;padding:28px 32px;">
                        <span style="display:inline-block;font-size:24px;font-weight:800;letter-spacing:-0.02em;color:#FFFFFF;">OSEP</span>
                        <span style="display:block;margin-top:4px;font-size:13px;color:#C7D2FE;">Event Planning Platform</span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:36px 32px 8px 32px;">
                        <h1 style="margin:0 0 16px 0;font-size:24px;line-height:1.3;font-weight:700;color:#0F172A;">
                            {{ $heading }}
                        </h1>
                        {{ $slot }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 32px 36px 32px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background-color:#1E3A8A;border-radius:12px;">
                                    <a href="{{ $url }}"
                                       style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                                        {{ $action }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0 0;font-size:13px;line-height:1.6;color:#64748B;">
                            If the button doesn't work, copy and paste this link into your browser:<br>
                            <a href="{{ $url }}" style="color:#1E3A8A;word-break:break-all;">{{ $url }}</a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="border-top:1px solid #E2E8F0;padding:24px 32px;background-color:#F8FAFC;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#64748B;">
                            You received this email because this address was used on OSEP.
                            If it wasn't you, you can safely ignore this message.
                        </p>
                        <p style="margin:12px 0 0 0;font-size:12px;color:#94A3B8;">
                            &copy; {{ date('Y') }} OSEP &mdash; Plan smarter. Create unforgettable events.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
