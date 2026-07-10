<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulateur FCP | Kori Asset Management</title>

    <!-- Favicon Kori -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/favicon.svg">
    
    <!-- Meta SEO -->
    <meta name="description" content="Simulez vos investissements périodiques et planifiez votre avenir financier avec les Fonds Communs de Placement de Kori Asset Management.">
    
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @livewireStyles
</head>
<body class="h-full text-slate-900 font-sans antialiased">

    <!-- Header / Navbar Principal -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-3">
                <!-- Symbole graphique Premium Kori -->
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-900 to-blue-950 flex items-center justify-center text-white font-extrabold shadow-md">
                    K
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-lg text-blue-950 tracking-tight leading-tight">KORI</span>
                    <span class="text-[10px] text-amber-600 font-extrabold uppercase tracking-widest leading-none">ASSET MANAGEMENT</span>
                </div>
            </a>
            
            <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                <a href="#" class="hover:text-blue-900 transition">Nos Fonds FCP</a>
                <a href="#" class="hover:text-blue-900 transition">Gestion de Portefeuille</a>
                <a href="#" class="hover:text-blue-900 transition">Actualités</a>
                <a href="#" class="hover:text-blue-900 transition">À propos</a>
            </nav>
            
            <div>
                <a href="#" class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-900 hover:bg-blue-950 text-white font-bold text-sm rounded-xl transition duration-150 shadow-sm">
                    Espace Client
                </a>
            </div>
        </div>
    </header>

    <!-- Zone principale -->
    <main class="py-12 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12 mt-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
            <div class="text-sm font-semibold tracking-wider text-slate-200">
                KORI ASSET MANAGEMENT S.A.
            </div>
            <p class="text-xs max-w-2xl mx-auto text-slate-500">
                Agréé par la COSUMAF / CREPMF. Les simulations présentées ont un caractère indicatif. Les performances passées ne préjugent pas des performances futures. Tout investissement en FCP comporte des risques de perte en capital.
            </p>
            <div class="text-xs text-slate-600 pt-4">
                &copy; 2026 Kori Asset Management. Tous droits réservés.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
