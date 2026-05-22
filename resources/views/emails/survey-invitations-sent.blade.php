<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Invitations Sent</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h2 style="color: #0d9488;">Survey Invitations Sent</h2>

    <p>{{ count($recipients) }} {{ Str::plural('invitation', count($recipients)) }} queued at {{ now()->format('M j, Y g:i A') }}.</p>

    <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="text-align: left; padding: 8px 12px; font-size: 13px; color: #6b7280;">#</th>
                <th style="text-align: left; padding: 8px 12px; font-size: 13px; color: #6b7280;">Name</th>
                <th style="text-align: left; padding: 8px 12px; font-size: 13px; color: #6b7280;">Email</th>
                <th style="text-align: left; padding: 8px 12px; font-size: 13px; color: #6b7280;">Send #</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recipients as $i => $recipient)
                <tr style="border-top: 1px solid #e5e7eb;">
                    <td style="padding: 8px 12px; font-size: 14px; color: #9ca3af;">{{ $i + 1 }}</td>
                    <td style="padding: 8px 12px; font-size: 14px;">{{ $recipient['name'] }}</td>
                    <td style="padding: 8px 12px; font-size: 14px;">{{ $recipient['email'] }}</td>
                    <td style="padding: 8px 12px; font-size: 14px; color: #6b7280;">{{ $recipient['send_number'] }} of 3</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
