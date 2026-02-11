<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Feedback Submitted</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #3b82f6; margin-bottom: 24px;">New Feedback Submitted</h1>

    <div style="background: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0;">
        <p style="margin: 8px 0;"><strong>Submitted By:</strong> {{ $feedback->user->name }}</p>
        <p style="margin: 8px 0;"><strong>Type:</strong> {{ $feedback->type->value }}</p>

        @if($feedback->from_page)
            <p style="margin: 8px 0;"><strong>From Page:</strong> {{ $feedback->from_page }}</p>
        @endif
    </div>

    <div style="margin: 24px 0;">
        <h2 style="font-size: 18px; color: #111827; margin-bottom: 12px;">Request</h2>
        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
            <p style="margin: 0; white-space: pre-wrap;">{{ $feedback->body }}</p>
        </div>
    </div>

    <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
        <p style="margin-bottom: 16px;">
            <a href="{{ route('feedback.index') }}"
               style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                View Feedback
            </a>
        </p>
    </div>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>Thank you for using ChoirTrends!</p>
    </div>

</body>
</html>
