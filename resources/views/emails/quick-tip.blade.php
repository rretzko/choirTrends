<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChoirTrends Quick Tip</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #3b82f6; margin-bottom: 24px;">{{ strip_tags($quickTip->header) }}</h1>

    @if ($quickTip->introduction)
        <div style="margin-bottom: 24px;">
            {!! $quickTip->introduction !!}
        </div>
    @endif

    <div style="background: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0;">
        {!! $quickTip->tip !!}
    </div>

    <div style="margin-top: 32px; padding: 16px; background: #eff6ff; border-radius: 8px;">
        <p style="margin: 0; color: #1e40af;">{{ $quickTip->resolvedCallToAction() }}</p>
    </div>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>{{ $quickTip->resolvedFooter() }}</p>
        <p style="font-size: 12px;">
            <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af; text-decoration: underline;">Unsubscribe from Quick Tips emails</a>
        </p>
    </div>

</body>
</html>
