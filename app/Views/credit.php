<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium 3D Glass Icons - Credit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

        :root {
            --bg-color: #080c14;
        }

        body {
            background-color: var(--bg-color);
            background-image: url('<?= base_url("images/credit.png") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: relative;
        }

        .icon-container {
            display: flex;
            gap: 30px;
            perspective: 1000px;
        }

        .icon-card {
            position: relative;
            width: 110px;
            height: 110px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            transform-style: preserve-3d;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
            overflow: hidden; /* For the shine effect */
            animation: float 6s ease-in-out infinite;
            animation-delay: var(--delay);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        /* The Interactive Shine Layer */
        .shine {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(255,255,255,0.15) 0%, transparent 60%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .icon-card:hover .shine {
            opacity: 1;
        }

        .icon-card i {
            font-size: 36px;
            color: #94a3b8;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: translateZ(20px);
        }

        .icon-card span {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 12px;
            opacity: 0;
            transform: translateZ(10px) translateY(10px);
            transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            color: white;
        }

        /* Hover Enhancements */
        .icon-card:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--color);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .icon-card:hover i {
            color: var(--color);
            transform: translateZ(60px) scale(1.1);
            filter: drop-shadow(0 0 10px var(--color));
        }

        .icon-card:hover span {
            opacity: 1;
            transform: translateZ(40px) translateY(0);
        }

        /* Underlying Glow */
        .icon-card::after {
            content: '';
            position: absolute;
            width: 100%; height: 100%;
            background: var(--color);
            filter: blur(40px);
            opacity: 0;
            transition: 0.4s;
            z-index: -1;
            bottom: -20px;
        }

        .icon-card:hover::after {
            opacity: 0.3;
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.05);
            padding: 10px 20px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(-5px);
        }
    </style>
</head>
<body>

    <a href="javascript:history.back()" class="back-btn">
        <i data-lucide="arrow-left" class="w-5 h-5"></i>
        <span>Kembali ke Dashboard</span>
    </a>

    <div class="icon-container">
        <a href="#" class="icon-card" style="--color: #ff0000; --delay: 0s;">
            <div class="shine"></div>
            <i class="fab fa-youtube"></i>
            <span>YouTube</span>
        </a>

        <a href="#" class="icon-card" style="--color: #0084ff; --delay: 0.5s;">
            <div class="shine"></div>
            <i class="fab fa-facebook-f"></i>
            <span>Facebook</span>
        </a>

        <a href="#" class="icon-card" style="--color: #ff0090; --delay: 1s;">
            <div class="shine"></div>
            <i class="fab fa-instagram"></i>
            <span>Instagram</span>
        </a>

        <a href="#" class="icon-card" style="--color: #ffffff; --delay: 1.5s;">
            <div class="shine"></div>
            <i class="fab fa-github"></i>
            <span>Github</span>
        </a>
    </div>

    <script>
        lucide.createIcons();

        const cards = document.querySelectorAll('.icon-card');

        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left; 
                const y = e.clientY - rect.top; 
                
                // Update shine position
                card.style.setProperty('--x', `${x}px`);
                card.style.setProperty('--y', `${y}px`);

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                // Tilt logic
                const rotateX = ((y - centerY) / centerY) * -25;
                const rotateY = ((x - centerX) / centerX) * 25;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
            });
        });
    </script>
</body>
</html>
