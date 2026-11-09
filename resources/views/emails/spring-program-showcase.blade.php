<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Is this your concert program?</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <p>{{ $firstName }},</p>

    <p>Someone recently posted this great Winter Concert program to ChoirTrends:</p>

    <div style="margin: 24px 0; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px; background-color: #f9fafb;">
        <p style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #111827;">{{ $programName }}</p>

        @foreach ($songsByEnsemble as $group)
            <p style="margin: 16px 0 4px; font-size: 14px; font-weight: 600; color: #111827;">{{ $group['ensembleName'] ?? 'Other' }}</p>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                @foreach ($group['songs'] as $songTitle)
                    <li style="margin-bottom: 4px; color: #111827;">
                        {{ $songTitle->song_title }}
                        @if ($songTitle->composer || $songTitle->arranger)
                            <span style="color: #6b7280;">
                                &mdash;
                                @if ($songTitle->composer)
                                    {{ $songTitle->composer->artist_name }}
                                @endif
                                @if ($songTitle->composer && $songTitle->arranger)
                                    /
                                @endif
                                @if ($songTitle->arranger)
                                    arr. {{ $songTitle->arranger->artist_name }}
                                @endif
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>

    <p><strong>Is this yours?</strong> Thank you! We'd love to see what you did last spring, too. Please post that program as well!</p>

    <p><strong>Not yours?</strong> We'd <em>also</em> love to see what you did last spring. Take a minute and share it with your choir community.</p>

    <p>Log in at <a href="https://choirtrends.com" style="color: #0d9488;">choirtrends.com</a>, then click <strong>Add Program</strong> in the sidebar. Upload a photo (JPG or PNG) or a PDF of your concert program, and ChoirTrends extracts the details automatically. (Your student rosters are never included.)</p>

    <p><strong>Too busy?</strong> Just forward your spring program to me at <a href="mailto:rick@mfrholdings.com" style="color: #0d9488;">rick@mfrholdings.com</a> and I'll add it for you.</p>

    <p>Thank you for helping build a resource for the entire choral community!</p>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
        <p style="margin: 0;">Warm regards,</p>
        <p style="margin: 4px 0; font-weight: 600;">Rick Retzko</p>
        <p style="margin: 0; color: #6b7280; font-size: 14px;"><a href="https://choirtrends.com" style="color: #0d9488;">choirtrends.com</a></p>
    </div>

    <p style="margin-top: 24px;"><strong>P.S.</strong> ChoirTrends is free, and it always will be. It's my gift to the choral community. But it only works when directors contribute. Every program you share makes the repertoire data richer and more useful for every director who searches it, including you. If ChoirTrends has ever helped you find a piece or spark an idea, please do your part and share your spring program today. It takes just a few minutes, and your colleagues are counting on it.</p>

</body>
</html>
