<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Response Received</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h2 style="color: #0d9488;">Survey Response Received</h2>

    <p><strong>From:</strong> {{ $surveyResponse->user->name }} ({{ $surveyResponse->user->email }})</p>
    <p><strong>Submitted:</strong> {{ $surveyResponse->created_at->format('M j, Y g:i A') }}</p>

    <h3 style="margin-top: 24px;">Reasons Selected</h3>
    <ul>
        @foreach ($surveyResponse->reasons as $reason)
            <li>{{ \App\Enums\SurveyReason::tryFrom($reason)?->label() ?? $reason }}</li>
        @endforeach
    </ul>

    @if ($surveyResponse->other_reason)
        <h3>Other Reason</h3>
        <p style="background: #f9fafb; padding: 12px; border-radius: 6px;">{{ $surveyResponse->other_reason }}</p>
    @endif

    @if ($surveyResponse->comments)
        <h3>Comments</h3>
        <p style="background: #f9fafb; padding: 12px; border-radius: 6px;">{{ $surveyResponse->comments }}</p>
    @endif

</body>
</html>
