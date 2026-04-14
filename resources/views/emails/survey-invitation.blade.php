<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We'd love your feedback</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <p>Hi {{ $user->name }},</p>

    <p>Thank you for registering on <strong>ChoirTrends</strong>! I noticed that you haven't uploaded any programs yet, and I wanted to reach out personally.</p>

    <p>In the interest of continually improving the site for choral directors like you, would you share with me your reason for holding off on uploading a program? Your feedback would be incredibly valuable.</p>

    <p>It only takes a moment — just click the button below:</p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $surveyUrl }}" style="display: inline-block; padding: 12px 28px; background-color: #0d9488; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">Share My Feedback</a>
    </div>

    <p style="color: #6b7280; font-size: 14px;">I respect your time — you'll receive at most {{ $remainingEmails }} more {{ Str::plural('reminder', $remainingEmails) }} about this, and then I'll stop asking.</p>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
        <p style="margin: 0;">Warmly,</p>
        <p style="margin: 4px 0; font-weight: 600;">Rick Retzko</p>
        <p style="margin: 0; color: #6b7280; font-size: 14px;">Founder, ChoirTrends</p>
    </div>

    <p style="color: #9ca3af; font-size: 12px; margin-top: 24px;">P.S. If you prefer not to click the button, you can copy and paste this web address into your browser:<br><a href="{{ $surveyUrl }}" style="color: #9ca3af; word-break: break-all;">{{ $surveyUrl }}</a></p>

</body>
</html>
