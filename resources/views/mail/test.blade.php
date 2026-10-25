<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hostinger Mail API connection confirmed</title>
    <style>
        @font-face { font-family: 'Montserrat'; src: url('{{ url('/fonts/montserrat/montserrat-latin.woff2') }}') format('woff2'); font-weight: 400 800; font-style: normal; font-display: swap; }
    </style>
</head>
<body style="margin:0;background:#f3f0e9;color:#19191d;font-family:'Montserrat','Segoe UI',Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f0e9;padding:30px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fffefa;border:1px solid #ded8cc;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#111113;padding:24px 32px;">
                            <img src="{{ $logoUrl }}" width="190" alt="Urgent Travel Services" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;">
                            <div style="color:#9a742d;font-size:10px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">Mail API connected</div>
                            <h1 style="margin:10px 0 15px;font-family:'Montserrat','Segoe UI',Arial,sans-serif;font-weight:800;font-size:34px;letter-spacing:-1px;color:#111113;">The connection works.</h1>
                            <p style="margin:0;color:#4c4942;font-size:14px;line-height:1.75;">This test message was sent from <strong>{{ $senderName }}</strong> through the Hostinger Mail API to {{ $recipient }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
