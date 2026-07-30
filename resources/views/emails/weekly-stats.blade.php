<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChoirTrends Stats</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h1 style="color: #3b82f6; margin-bottom: 24px;">ChoirTrends Stats</h1>

    <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
        Snapshot taken {{ $snapshot->captured_at->format('M j, Y g:i A') }}
    </p>

    @php
        $metrics = [
            'verified_users_count' => 'Verified Users',
            'schools_count' => 'Schools',
            'programs_count' => 'Programs',
            'song_titles_count' => 'Song Titles',
        ];

        $periods = [
            'week' => 'vs Last Week',
            'month' => 'vs Last Month',
            'quarter' => 'vs Last Quarter',
            'year' => 'vs Last Year',
        ];

        $formatDelta = function (?int $delta): string {
            if ($delta === null) {
                return '—';
            }

            return $delta >= 0 ? "+{$delta}" : (string) $delta;
        };
    @endphp

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <thead>
            <tr style="background: #f9fafb;">
                <th style="text-align: left; padding: 8px 12px; border-bottom: 2px solid #e5e7eb;">Metric</th>
                <th style="text-align: right; padding: 8px 12px; border-bottom: 2px solid #e5e7eb;">Current</th>
                @foreach ($periods as $label)
                    <th style="text-align: right; padding: 8px 12px; border-bottom: 2px solid #e5e7eb;">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($metrics as $column => $label)
                <tr>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #e5e7eb;">{{ $label }}</td>
                    <td style="text-align: right; padding: 8px 12px; border-bottom: 1px solid #e5e7eb;"><strong>{{ $snapshot->{$column} }}</strong></td>
                    @foreach ($periods as $period => $label2)
                        <td style="text-align: right; padding: 8px 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">
                            {{ $formatDelta($comparisons[$period]?->{$column} !== null ? $snapshot->{$column} - $comparisons[$period]->{$column} : null) }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
        <p>Automated weekly report from ChoirTrends.com</p>
    </div>

</body>
</html>
