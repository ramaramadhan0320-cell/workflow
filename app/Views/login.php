<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Workflow</title>
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            /* Gunakan base_url() agar kompatibel dengan berbagai domain/Cloudflare */
            background-image: url('<?= base_url("images/bg.jpg") ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Custom placeholder color agar lebih putih transparan */
        ::placeholder {
            color: rgba(255, 255, 255, 0.8) !important;
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-4">

    <div class="glass w-full max-w-[400px] p-10 rounded-[2.5rem] shadow-2xl flex flex-col items-center">
        
        <div class="mb-8">
            <div class="w-24 h-24 rounded-full bg-[#0f172a]/80 flex items-center justify-center p-4 shadow-inner border border-white/10">
                <img src="<?= base_url("images/logo-3.png") ?>" alt="Workflow Logo" class="w-full h-full object-contain">
            </div>
        </div>

        <h1 class="text-3xl font-bold text-[#1e3a5f] tracking-[0.2em] uppercase mb-10">
            Workflow
        </h1>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="w-full mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-xl text-red-100 text-sm text-center">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('login/process') ?>" class="w-full flex flex-col gap-5">
            <?= csrf_field() ?>
            
            <input type="text" 
                   name="username" 
                   placeholder="Username" 
                   required
                   class="w-full bg-transparent border-2 border-white/60 rounded-full py-3 px-6 text-white focus:outline-none focus:border-white focus:ring-1 focus:ring-white transition-all">

            <input type="password" 
                   name="password" 
                   placeholder="Password" 
                   required
                   class="w-full bg-transparent border-2 border-white/60 rounded-full py-3 px-6 text-white focus:outline-none focus:border-white focus:ring-1 focus:ring-white transition-all">

            <div class="mt-4 flex justify-center">
                <button type="submit" 
                        class="bg-white text-[#1e3a5f] font-bold py-3 px-14 rounded-full hover:bg-opacity-90 active:scale-95 transition-all shadow-lg uppercase tracking-wider">
                    Login
                </button>
            </div>

        </form>

    </div>

</body>
</html>
