<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulateur FCP | Kori Asset Management</title>

    <!-- Meta SEO -->
    <meta name="description"
        content="Simulez vos investissements périodiques et planifiez votre avenir financier avec les Fonds Communs de Placement de Kori Asset Management.">

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS CDN avec Charte Kori -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        kori: {
                            brown: '#4A2306',
                            gold: '#e5a900',
                            dark: '#2d1402',
                            light: '#fcfaf7',
                        }
                    }
                }
            }
        }
    </script>

    <!-- ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- intl-tel-input CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/css/intlTelInput.css">
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/intlTelInput.min.js"></script>

    @livewireStyles
</head>

<body class="h-full text-slate-900 font-sans antialiased">

    <!-- Header / Navbar Principal -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="/" class="flex flex-col items-start select-none">
                <div class="flex items-center space-x-1 font-bold text-2xl tracking-tight text-kori-brown uppercase">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="h-10 w-auto">
                </div>
            </a>

            {{-- <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                <a href="#" class="hover:text-kori-brown transition">Nos Fonds FCP</a>
                <a href="#" class="hover:text-kori-brown transition">Gestion de Portefeuille</a>
                <a href="#" class="hover:text-kori-brown transition">Actualités</a>
                <a href="#" class="hover:text-kori-brown transition">À propos</a>
            </nav>
 --}}
            <div>
                <a href="https://espace-client.koriassetmanagement.com/" target="_blank"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-kori-brown hover:bg-kori-dark text-white font-bold text-sm rounded-xl transition duration-150 shadow-sm">
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
    <footer class="bg-kori-dark text-slate-400 py-12 mt-12 border-t border-kori-brown/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
            <div class="text-sm font-semibold tracking-wider text-slate-200 uppercase">
                Kori Asset Management S.A.
            </div>
            <p class="text-xs max-w-2xl mx-auto text-slate-500">
                Agréé par la COSUMAF. Les simulations présentées ont un caractère indicatif. Les performances passées ne
                préjugent pas des performances futures. Tout investissement en FCP comporte des risques de perte en
                capital en zone CEMAC.
            </p>
            <div class="text-xs text-slate-600 pt-4">
                &copy; 2026 Kori Asset Management. Tous droits réservés.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>

</html>
