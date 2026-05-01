<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow IoT Scanner</title>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #0f172a;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: white;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }
        .scanner-container {
            position: relative;
            width: 90%;
            max-width: 500px;
            aspect-ratio: 4/3;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: #000;
        }
        #canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .scan-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 2px solid var(--primary);
            box-shadow: inset 0 0 100px rgba(0,0,0,0.5);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--primary);
            box-shadow: 0 0 15px var(--primary);
            animation: scan 3s infinite linear;
        }
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
        .status-panel {
            margin-top: 20px;
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-width: 250px;
        }
        #result {
            font-size: 1.1em;
            font-weight: 600;
            margin-top: 5px;
            color: var(--primary);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .badge-online { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .badge-loading { background: rgba(59, 130, 246, 0.2); color: var(--primary); animation: pulse 1.5s infinite; }
        
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
</head>
<body>

    <div class="scanner-container">
        <canvas id="canvas"></canvas>
        <div class="scan-overlay">
            <div class="scan-line"></div>
        </div>
    </div>

    <div class="status-panel">
        <div id="badge" class="badge badge-loading">Menghubungkan...</div>
        <div id="result">Mencari QR Code...</div>
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d', { WILL_READ_FREQUENTLY: true });
        const resultText = document.getElementById('result');
        const badge = document.getElementById('badge');

        // Parameter dari URL
        const urlParams = new URLSearchParams(window.location.search);
        const streamUrl = urlParams.get('stream');
        const apiUrl = "<?= base_url('/api-android/integration/push') ?>";

        let isProcessing = false;
        const img = new Image();
        img.crossOrigin = "anonymous";
        img.src = streamUrl;

        img.onload = () => {
            badge.innerText = "Kamera Aktif";
            badge.className = "badge badge-online";
            tick();
        };

        img.onerror = () => {
            badge.innerText = "Error Kamera";
            badge.className = "badge";
            badge.style.background = "rgba(239, 68, 68, 0.2)";
            badge.style.color = "#ef4444";
            resultText.innerText = "Gagal memuat aliran video";
        };

        function tick() {
            if (img.complete && img.naturalWidth > 0) {
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;

                // Gambar frame kamera
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                if (!isProcessing) {
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);

                    if (code && code.data.trim() !== "") {
                        submitScan(code.data);
                    }
                }
            }
            requestAnimationFrame(tick);
        }

        function submitScan(qrData) {
            isProcessing = true;
            resultText.innerText = "Memproses Data...";
            resultText.style.color = "#f59e0b";

            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ raw_data: qrData })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    resultText.innerText = "BERHASIL: " + (data.message || "Absensi Tercatat");
                    resultText.style.color = "#10b981";
                } else {
                    resultText.innerText = "GAGAL: " + (data.message || "Data tidak dikenal");
                    resultText.style.color = "#ef4444";
                }

                setTimeout(() => {
                    isProcessing = false;
                    resultText.innerText = "Mencari QR Code...";
                    resultText.style.color = "#3b82f6";
                }, 3000);
            })
            .catch(err => {
                resultText.innerText = "Server Error!";
                resultText.style.color = "#ef4444";
                setTimeout(() => { isProcessing = false; }, 2000);
            });
        }
    </script>
</body>
</html>
