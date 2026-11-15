<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AgendaGo — Tu negocio, organizado en un solo lugar</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-warm">
    <div class="min-h-screen">
        <!-- Encabezado -->
        <header class="bg-petrol">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-white font-extrabold text-lg tracking-tight">agenda<span class="text-turquoise-500">Go</span></span>
                </div>
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 transition">
                    Iniciar sesión
                </a>
            </div>
        </header>

        <!-- Hero -->
        <section class="bg-petrol">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
                <p class="text-turquoise-500 text-xs font-bold uppercase tracking-widest">Plataforma de reservas para negocios de servicios</p>
                <h1 class="text-white text-3xl sm:text-5xl font-extrabold mt-4 max-w-3xl mx-auto leading-tight">
                    Tu negocio, organizado en un solo lugar
                </h1>
                <p class="text-white/70 text-base sm:text-lg mt-4 max-w-2xl mx-auto">
                    AgendaGo reúne la agenda de tus profesionales, el catálogo de servicios,
                    las reservas por WhatsApp y el control de tu negocio en una sola plataforma.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl bg-turquoise-600 hover:bg-turquoise-700 text-white font-semibold transition">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-semibold border border-white/20 transition">
                        Registrarse
                    </a>
                </div>
                <p class="text-white/50 text-xs mt-3">El registro se realiza con la invitación de tu negocio.</p>
            </div>
        </section>

        <!-- Funciones -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <h2 class="text-2xl font-extrabold text-center">Todo lo que tu negocio necesita</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mt-8">
                @foreach ([
                    ['calendar', 'Agenda inteligente', 'Vistas diaria y semanal, disponibilidad automática y gestión de reservas sin conflictos.'],
                    ['users', 'Profesionales y servicios', 'Catálogo, horarios, descansos, vacaciones y festivos configurables por negocio.'],
                    ['bell', 'Reservas por WhatsApp', 'Tus clientes reservan desde su número favorito y tú todo queda registrado.'],
                    ['money', 'Membresías y pagos', 'Control de membresías, pagos de servicios e indicadores operativos.'],
                    ['shield', 'Multiempresa y segura', 'Cada negocio con sus datos aislados, roles definidos y verificación en dos pasos.'],
                    ['chart', 'Reportes claros', 'Indicadores y exportaciones en CSV, Excel y PDF para tomar decisiones.'],
                ] as [$icon, $titulo, $texto])
                    <div class="bg-white rounded-2xl border border-warm-200 shadow-card p-6">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center">
                            <x-icon :name="$icon" />
                        </div>
                        <h3 class="font-bold text-petrol mt-3">{{ $titulo }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $texto }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Llamado a la acción -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="bg-petrol rounded-2xl shadow-card p-8 sm:p-12 text-center">
                <h2 class="text-white text-2xl sm:text-3xl font-extrabold">Empieza a organizar tu negocio hoy</h2>
                <p class="text-white/60 mt-2">Accede con tu cuenta o solicita una invitación a tu administrador.</p>
                <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl bg-turquoise-600 hover:bg-turquoise-700 text-white font-semibold transition">
                        Iniciar sesión
                    </a>
                    <a href="{{ route('login') }}" class="px-6 py-3 rounded-2xl bg-white text-petrol font-semibold hover:bg-warm-100 transition">
                        Registrarse
                    </a>
                </div>
            </div>
        </section>

        <footer class="border-t border-warm-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-application-logo class="h-6 w-6" />
                    <span class="text-petrol font-bold text-sm">agenda<span class="text-turquoise-600">Go</span></span>
                </div>
                <p class="text-xs text-gray-500">© {{ date('Y') }} AgendaGo. Tu negocio, organizado en un solo lugar.</p>
            </div>
        </footer>
    </div>
</body>
</html>
