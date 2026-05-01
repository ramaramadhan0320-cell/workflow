<?php
$userId    = "1";
$secretKey = "RADEN_SECRET_99";
$timestamp = time(); // Mengambil waktu sekarang

// Rumus Hash: Gabungkan ID dan Timestamp, lalu kunci dengan Secret Key
$signature = hash_hmac('sha256', $userId . $timestamp, $secretKey);
$payload = $userId . ':' . $timestamp . ':' . $signature;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Payload</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; color: #111827; margin: 0; padding: 24px; }
        .card { background: #ffffff; border-radius: 24px; box-shadow: 0 24px 80px rgba(15,23,42,.08); max-width: 520px; margin: auto; padding: 24px; }
        .payload { word-break: break-all; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-top: 16px; color: #0f172a; }
        .qr-box { display: flex; justify-content: center; margin-top: 24px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <div class="card">
        <h1 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 12px;">QR Payload Generator</h1>
        <p style="margin: 0; color: #475569;">Silakan gunakan QR di bawah ini untuk scan, atau salin payload berikut ke Postman.</p>
        <div class="payload">
            <strong>Payload:</strong><br>
            <?= htmlentities($payload, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="qr-box">
            <div id="qrcode"></div>
        </div>
    </div>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: <?= json_encode($payload) ?>,
            width: 240,
            height: 240,
            colorDark: '#111827',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
