<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $inquiry->type === 'contact' ? 'Wij hebben uw bericht ontvangen' : 'Wij hebben uw transferaanvraag ontvangen' }}</title>
    <style>
        @font-face { font-family: 'Montserrat'; src: url('{{ url('/fonts/montserrat/montserrat-latin.woff2') }}') format('woff2'); font-weight: 400 800; font-style: normal; font-display: swap; }
    </style>
</head>
<body style="margin:0;background:#f3f0e9;color:#19191d;font-family:'Montserrat','Segoe UI',Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f0e9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fffefa;border:1px solid #ded8cc;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#111113;padding:24px 34px;">
                            <img src="{{ $logoUrl }}" width="190" alt="Urgent Travel Services" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:38px 34px;">
                            <div style="color:#9a742d;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Aanvraag ontvangen</div>
                            <h1 style="margin:10px 0 18px;font-family:'Montserrat','Segoe UI',Arial,sans-serif;font-weight:800;font-size:35px;line-height:1.15;letter-spacing:-1px;color:#111113;">Bedankt, {{ $inquiry->name }}.</h1>
                            <p style="margin:0 0 14px;color:#4c4942;font-size:15px;line-height:1.75;">
                                {{ $inquiry->type === 'contact' ? 'Wij hebben uw bericht ontvangen en nemen zo snel mogelijk contact met u op.' : 'Wij hebben uw transferaanvraag ontvangen. Ons team bekijkt deze persoonlijk.' }}
                            </p>
                            <p style="margin:0 0 26px;color:#4c4942;font-size:15px;line-height:1.75;">
                                Uw referentie is <strong style="color:#111113;">{{ $inquiry->reference }}</strong>.
                                @if ($inquiry->type !== 'contact')
                                    Dit is nog geen bevestigde boeking. Wij nemen contact op om beschikbaarheid en de prijs te bevestigen voordat uw reis definitief wordt geregeld.
                                @endif
                            </p>

                            @if ($inquiry->type !== 'contact' && filled($inquiry->pickup))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:24px 0;border:1px solid #e4ded2;background:#f8f6f1;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <div style="color:#99732d;font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Uw reis</div>
                                            <div style="margin-top:7px;font-family:'Montserrat','Segoe UI',Arial,sans-serif;font-weight:700;font-size:22px;line-height:1.35;color:#111113;">{{ $inquiry->pickup }} → {{ $inquiry->dropoff }}</div>
                                            <div style="margin-top:8px;color:#746f66;font-size:12px;">
                                                @if ($inquiry->pickup_date){{ $inquiry->pickup_date->format('d-m-Y') }}@endif
                                                @if ($inquiry->pickup_time) om {{ $inquiry->pickup_time }}@endif
                                                @if ($inquiry->passengers) · {{ $inquiry->passengers }} passagier{{ $inquiry->passengers === 1 ? '' : 's' }}@endif
                                            </div>
                                            @if ($inquiry->vehicle_name)
                                                <div style="margin-top:8px;color:#746f66;font-size:12px;">{{ $inquiry->vehicle_name }}@if ($inquiry->estimated_total !== null) · Geschat EUR {{ number_format((float) $inquiry->estimated_total, 2, ',', '.') }}@endif</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if (! empty($inquiry->selected_extras))
                                <div style="margin:0 0 24px;border:1px solid #e4ded2;background:#f8f6f1;padding:15px 18px;">
                                    <div style="margin-bottom:6px;color:#99732d;font-size:9px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;">Uw extra’s</div>
                                    @foreach ($inquiry->selected_extras as $extra)
                                        <div style="display:flex;justify-content:space-between;gap:15px;border-top:1px solid #e8e2d8;padding:5px 0;color:#4f4a43;font-size:11px;">
                                            <span>{{ $extra['name_nl'] ?? $extra['name'] ?? 'Extra' }}{{ ($extra['pricing_type'] ?? 'per_item') === 'per_item' ? ' × '.$extra['quantity'] : '' }}</span>
                                            <strong>EUR {{ number_format((float) ($extra['line_total'] ?? 0), 2, ',', '.') }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <p style="margin:0 0 8px;color:#746f66;font-size:12px;line-height:1.7;">Wilt u iets toevoegen of heeft u een dringende wijziging? Neem dan direct contact met ons op:</p>
                            @if ($contactPhone)
                                <p style="margin:0 0 4px;font-size:14px;"><a href="tel:{{ preg_replace('/[^+0-9]/', '', $contactPhone) }}" style="color:#111113;font-weight:700;text-decoration:none;">{{ $contactPhone }}</a></p>
                            @endif
                            @if ($contactEmail)
                                <p style="margin:0;font-size:14px;"><a href="mailto:{{ $contactEmail }}" style="color:#8d6727;text-decoration:none;">{{ $contactEmail }}</a></p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#111113;padding:20px 34px;color:#8f8b84;font-size:10px;line-height:1.6;">
                            {{ $senderName }} · Privéchauffeur en luchthaventransfers in heel Nederland.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
