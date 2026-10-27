<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $brand['name'] }} — Caregiver proposal</title>
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        @page { margin: 34px 38px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.5; }
        .brand { border-bottom: 3px solid {{ $brand['primary_color'] }}; padding-bottom: 14px; margin-bottom: 22px; }
        .brand-name { color: {{ $brand['primary_color'] }}; font-size: 22px; font-weight: bold; }
        .tagline { color: #6b7280; margin-top: 2px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 15px; color: {{ $brand['primary_color'] }}; margin: 0 0 10px; }
        .request { background: #f3f4f6; padding: 12px 14px; border-radius: 4px; margin: 16px 0 20px; }
        .candidate { border: 1px solid #d1d5db; border-radius: 5px; padding: 14px; margin: 12px 0; page-break-inside: avoid; }
        .muted { color: #6b7280; }
        .chip { display: inline-block; background: #ecfdf5; color: #166534; border-radius: 10px; padding: 3px 8px; margin: 3px 3px 0 0; }
        footer { position: fixed; bottom: -18px; left: 0; right: 0; text-align: center; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <header class="brand">
        <div class="brand-name">{{ $brand['name'] }}</div>
        <div class="tagline">{{ $brand['tagline'] }}</div>
    </header>

    <h1>Caregiver options for your service request</h1>
    <p class="muted">Proposal reference {{ $proposal->id }} · Prepared {{ now()->format('d M Y') }}</p>

    <section class="request">
        <h2>Requested care</h2>
        <div><strong>Service:</strong> {{ $serviceRequest->service->name }}</div>
        <div><strong>Arrangement:</strong> {{ ucfirst($serviceRequest->live_in_out) }}</div>
        <div><strong>Schedule:</strong> {{ implode(', ', $serviceRequest->schedule_json['weekdays'] ?? []) }} · {{ $serviceRequest->schedule_json['start_time'] ?? '' }}–{{ $serviceRequest->schedule_json['end_time'] ?? '' }}</div>
    </section>

    @foreach($candidates as $candidate)
        <section class="candidate">
            <h2>{{ $candidate['label'] }}</h2>
            <div><strong>First name:</strong> {{ $candidate['first_name'] }}</div>
            <div><strong>Availability:</strong> {{ $candidate['available_from'] ?? 'To be confirmed' }}</div>
            <div><strong>Area preference:</strong> {{ $candidate['zone_match'] ? 'Aligns with your service area' : 'To be confirmed' }}</div>
            <div class="mt-2"><strong>Relevant skills:</strong>
                @forelse($candidate['skills'] as $skill)<span class="chip">{{ $skill }}</span>@empty <span class="muted">To be discussed</span>@endforelse
            </div>
        </section>
    @endforeach

    <p class="muted">Your coordinator can answer questions and confirm next steps. Final arrangements are subject to your acceptance and a signed service agreement.</p>
    <footer>{{ $brand['name'] }} · Caregiver proposal · Reference {{ $proposal->id }}</footer>
</body>
</html>
