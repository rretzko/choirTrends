<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registered</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #3b82f6; margin-bottom: 24px;">New User Registered</h1>

    <div style="background: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0;">
        <p style="margin: 8px 0;"><strong>Name:</strong> {{ $user->name }}</p>
        <p style="margin: 8px 0;"><strong>Email:</strong> {{ $user->email }}</p>
        <p style="margin: 8px 0;"><strong>Registered:</strong> {{ $user->created_at?->format('M j, Y g:i A') ?? 'N/A' }}</p>
    </div>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>Hi {{ $user->name }} - Thanks for registering on ChoirTrends.com!</p>
        <p>Don't hesitate to reach out if you hit any bugs or have suggestions to improve the app!  Simply click the Feedback link & you'll get to me.</p>
        <p>Thank you for using ChoirTrends!</p>
        <p>Best - </p>
        <p>Rick Retzko</p>
        <p>{{ config('app.founder') }}</p>
    </div>

</body>
</html>
