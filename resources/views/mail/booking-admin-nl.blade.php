<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nieuwe {{ $inquiry->type === 'contact' ? 'aanvraag' : 'transferaanvraag' }}</title>
    <style>
        @font-face { font-family: 'Montserrat'; src: url('{{ url('/fonts/montserrat/montserrat-latin.woff2') }}') format('woff2'); font-weight: 400 800; font-style: normal; font-display: swap; }
    </style>
</head>
<body style="margin:0;background:#f3f0e9;color:#19191d;font-family:'Montserrat','Segoe UI',Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f0e9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px;background:#fffefa;border:1px solid #ded8cc;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#111113;padding:24px 34px;">
                            <img src="{{ $logoUrl }}" width="190" alt="Urgent Travel Services" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px;">
                            <div style="color:#9a742d;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Nieuwe websiteaanvraag</div>
                            <h1 style="margin:10px 0 8px;font-family:'Montserrat','Segoe UI',Arial,sans-serif;font-weight:800;font-size:32px;line-height:1.15;letter-spacing:-1px;color:#111113;">{{ $inquiry->type === 'contact' ? 'Nieuw contactbericht' : 'Nieuwe transferaanvraag' }}</h1>
                            <p style="margin:0 0 25px;color:#746f66;font-size:14px;line-height:1.7;">Referentie <strong style="color:#111113;">{{ $inquiry->reference }}</strong>, ontvangen op {{ $inquiry->created_at->format('d-m-Y \o\m H:i') }}.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;">
                                @foreach ([
                                    'Klant' => $inquiry->name,
                                    'E-mail' => $inquiry->email,
                                    'Telefoon' => $inquiry->phone,
                                    'Ophaaladres' => $inquiry->pickup,
                                    'Bestemming' => $inquiry->dropoff,
                                    'Datum' => $inquiry->pickup_date?->format('d-m-Y'),
                                    'Tijd' => $inquiry->pickup_time,
                                    'Dienst' => $inquiry->service_type,
                                    'Soort reis' => $inquiry->booking_mode === 'hourly' ? 'Chauffeur per uur' : ($inquiry->trip_type === 'return' ? 'Retourtransfer' : ($inquiry->trip_type === 'one_way' ? 'Enkele transfer' : null)),
                                    'Voertuig' => $inquiry->vehicle_name,
                                    'Afstand over de weg' => $inquiry->route_distance_km !== null ? $inquiry->route_distance_km.' km' : null,
                                    'Rijtijd' => $inquiry->route_duration_minutes ? $inquiry->route_duration_minutes.' minuten' : null,
                                    'Duur' => $inquiry->hourly_duration_hours ? $inquiry->hourly_duration_hours.' uur' : null,
                                    'Vlucht' => $inquiry->flight_number,
                                    'Passagiers' => $inquiry->passengers,
                                    'Bagage' => $inquiry->luggage,
                                    'Betaling' => $inquiry->payment_method === 'cash' ? 'Contant bij ophalen' : ($inquiry->payment_method === 'card' ? 'Kaart bij ophalen' : null),
                                    'Factuuradres' => $inquiry->invoice_address,
                                    'Geschat totaal' => $inquiry->estimated_total !== null ? 'EUR '.number_format((float) $inquiry->estimated_total, 2, ',', '.') : null,
                                    'Bericht' => $inquiry->message,
                                ] as $label => $value)
                                    @if (filled($value))
                                        <tr>
                                            <td style="width:130px;border-bottom:1px solid #ebe6dc;padding:10px 12px 10px 0;color:#8b857b;vertical-align:top;">{{ $label }}</td>
                                            <td style="border-bottom:1px solid #ebe6dc;padding:10px 0;color:#292723;font-weight:600;vertical-align:top;">{{ $value }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>

                            @if (! empty($inquiry->selected_extras))
                                <div style="margin-top:18px;border:1px solid #e4ded2;background:#f8f6f1;padding:15px 17px;">
                                    <div style="margin-bottom:7px;color:#99732d;font-size:9px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;">Gekozen extra’s</div>
                                    @foreach ($inquiry->selected_extras as $extra)
                                        <div style="display:flex;justify-content:space-between;gap:20px;border-top:1px solid #e8e2d8;padding:6px 0;color:#4f4a43;font-size:11px;">
                                            <span>{{ $extra['name_nl'] ?? $extra['name'] ?? 'Extra' }}{{ ($extra['pricing_type'] ?? 'per_item') === 'per_item' ? ' × '.$extra['quantity'] : '' }}</span>
                                            <strong style="color:#19191d;">EUR {{ number_format((float) ($extra['line_total'] ?? 0), 2, ',', '.') }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:28px;">
                                <tr>
                                    <td style="background:#d2a24c;border-radius:3px;">
                                        <a href="{{ $adminUrl }}" style="display:inline-block;padding:13px 20px;color:#111113;font-size:11px;font-weight:700;letter-spacing:1px;text-decoration:none;text-transform:uppercase;">Open aanvragen</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f7f5ef;padding:18px 34px;color:#8c877d;font-size:10px;">
                            Deze melding is verstuurd via de Hostinger Mail API. Antwoord de klant direct via {{ $inquiry->email }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
