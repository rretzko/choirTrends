<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Program Processed' : 'Processing Failed' }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    @if($success)
        <h1 style="color: #10b981; margin-bottom: 24px;">Concert Program Processed Successfully!</h1>

        <p style="margin-bottom: 16px;">Your concert program has been successfully processed and analyzed.</p>

        @if(!empty($programData['event_name']))
            <div style="background: #f9fafb; border-left: 4px solid #10b981; padding: 16px; margin: 24px 0;">
                <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #111827;">Event Details</h2>

                @if($programData['event_name'])
                    <p style="margin: 8px 0;"><strong>Event:</strong> {{ $programData['event_name'] }}</p>
                @endif

                @if($programData['event_date'])
                    <p style="margin: 8px 0;"><strong>Date:</strong> {{ $programData['event_date'] }}</p>
                @endif

                @if($programData['school_name'])
                    <p style="margin: 8px 0;"><strong>School:</strong> {{ $programData['school_name'] }}</p>
                @endif

                @if($programData['director_name'])
                    <p style="margin: 8px 0;"><strong>Director:</strong> {{ $programData['director_name'] }}</p>
                @endif
            </div>
        @endif

        @if(!empty($programData['ensembles']))
            <div style="margin: 24px 0;">
                <h2 style="font-size: 18px; color: #111827; margin-bottom: 12px;">Ensembles & Repertoire</h2>
                <p style="color: #6b7280; margin-bottom: 16px;">
                    Found {{ count($programData['ensembles']) }} ensemble(s) with
                    {{ collect($programData['ensembles'])->sum(fn($e) => count($e['songs'] ?? [])) }} song(s)
                </p>

                @foreach($programData['ensembles'] as $ensemble)
                    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <h3 style="margin: 0 0 12px 0; color: #1f2937; font-size: 16px;">
                            {{ $ensemble['name'] ?? 'Unnamed Ensemble' }}
                        </h3>

                        @if(!empty($ensemble['songs']))
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach($ensemble['songs'] as $song)
                                    <li style="margin: 6px 0; color: #4b5563;">
                                        <strong>{{ $song['title'] ?? 'Untitled' }}</strong>
                                        @if(!empty($song['composer']))
                                            <br><span style="font-size: 14px; color: #6b7280;">by {{ $song['composer'] }}</span>
                                        @endif
                                        @if(!empty($song['arranger']))
                                            <br><span style="font-size: 14px; color: #6b7280;">arr. {{ $song['arranger'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p style="color: #9ca3af; font-style: italic; margin: 0;">No songs listed</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <p style="margin-bottom: 16px;">
                <a href="{{ route('addProgram') }}"
                   style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                    View & Edit Program Details
                </a>
            </p>
            <p style="color: #6b7280; font-size: 14px;">
                You can review and edit the extracted information before saving.
            </p>
        </div>

    @else
        <h1 style="color: #ef4444; margin-bottom: 24px;">Program Processing Failed</h1>

        <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; margin: 24px 0;">
            <p style="margin: 0; color: #991b1b;">
                <strong>Error:</strong> {{ $errorMessage ?? 'An unknown error occurred while processing your program.' }}
            </p>
        </div>

        <p style="margin-bottom: 16px;">Something didn’t work as expected. Please try again—and if the issue persists, click Feedback to report it so we can fix it quickly.</p>

        <div style="margin-top: 32px;">
            <p>
                <a href="{{ route('addProgram') }}"
                   style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                    Try Again
                </a>
            </p>
        </div>
    @endif

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>Thank you for using ChoirTrends!</p>
        <p style="margin-top: 8px;">
            If you have any questions, please don't hesitate to contact us.
        </p>
    </div>

</body>
</html>
