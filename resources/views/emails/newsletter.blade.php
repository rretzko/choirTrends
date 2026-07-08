<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChoirTrends Newsletter</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #3b82f6; margin-bottom: 24px;">{{ $data['headline'] }}</h1>

    <p>Hi {{ $userName }},</p>

    <p>{{ $data['intro'] }}</p>

    @if (!empty($data['sections']))
        @foreach ($data['sections'] as $section)
            <h2 style="color: #3b82f6; margin-top: 32px; margin-bottom: 16px;">{{ $section['title'] }}</h2>

            <div style="background: #f9fafb; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0;">
                {!! $section['body'] !!}
            </div>
        @endforeach
    @endif

    @if (!empty($data['cta_text']) && !empty($data['cta_url']))
        <div style="margin-top: 32px; text-align: center;">
            <a href="{{ $data['cta_url'] }}" style="display: inline-block; background: #3b82f6; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">{!! $data['cta_text'] !!}</a>
        </div>
    @endif

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>{{ $data['closing'] ?? 'Thank you for being part of the ChoirTrends community!' }}</p>
        <p>Warm regards,<br>Rick Retzko<br>ChoirTrends</p>
    </div>

</body>
</html>
