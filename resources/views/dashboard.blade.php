<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold">Panel principal</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($panelPlataforma)
                <!-- Plataforma (superadministrador) -->
                <div class="bg-petrol rounded-2xl shadow-card p-6 sm:p-8">
                    <p class="text-turquoise-500 text-xs font-bold uppercase tracking-widest">Administración SaaS</p>
                    <h3 class="text-white text-2xl sm:text-3xl font-extrabold mt-1">Panel de la plataforma</h3>
                    <p class="text-white/60 text-sm mt-1">Negocios, membresías e ingresos de AgendaGo.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <x-stat title="Negocios totales" :value="$negociosTotal" icon="briefcase" />
                    <x-stat title="Negocios activos" :value="$negociosActivos" icon="check" />
                    <x-stat title="Membresías activas" :value="$membresiasActivas" icon="shield" />
                    <x-stat title="Membresías por vencer" :value="$membresiasPorVencer" icon="bell" hint="Próximos 5 días" />
                    <x-stat title="Ingresos de membresías" value="${{ number_format($ingresosMembresias, 0, ',', '.') }}" icon="money" hint="Mes en curso (verificados)" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                        <div class="flex items-center justify-between p-5 pb-0">
                            <h3 class="text-lg font-bold">Vencimientos próximos</h3>
                            <a href="{{ route('admin.memberships') }}" class="text-sm font-semibold text-turquoise-700 hover:underline">Ver membresías →</a>
                        </div>
                        <div class="p-5">
                            @if ($vencimientos->isEmpty())
                                <x-empty-state icon="check" title="Sin vencimientos próximos" message="Ninguna membresía vence en los próximos 5 días." />
                            @else
                                <ul class="divide-y divide-warm-100">
                                    @foreach ($vencimientos as $membresia)
                                        <li class="flex items-center justify-between gap-3 py-3">
                                            <div>
                                                <p class="text-sm font-semibold text-petrol">{{ $membresia->business?->name }}</p>
                                                <p class="text-xs text-gray-500">Vence el {{ $membresia->ends_on }}</p>
                                            </div>
                                            <a href="{{ route('admin.businesses.show', $membresia->business_id) }}" class="text-sm font-semibold text-turquoise-700 hover:underline">Gestionar</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                        <div class="flex items-center justify-between p-5 pb-0">
                            <h3 class="text-lg font-bold">Negocios recientes</h3>
                            <a href="{{ route('admin.pantalla') }}" class="text-sm font-semibold text-turquoise-700 hover:underline">Ver todos →</a>
                        </div>
                        <div class="p-5">
                            @if ($negociosRecientes->isEmpty())
                                <x-empty-state icon="briefcase" title="Sin negocios" message="Registra el primer negocio de la plataforma." />
                            @else
                                <ul class="divide-y divide-warm-100">
                                    @foreach ($negociosRecientes as $item)
                                        <li class="flex items-center justify-between gap-3 py-3">
                                            <div>
                                                <p class="text-sm font-semibold text-petrol">{{ $item->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $item->created_at->format('d/m/Y') }}</p>
                                            </div>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $item->active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $item->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="{{ route('admin.pantalla') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="briefcase" /></div>
                        <div class="font-bold text-petrol mt-3">Negocios</div>
                        <div class="text-sm text-gray-500 mt-1">Altas, edición, activación y administradores.</div>
                    </a>
                    <a href="{{ route('admin.memberships') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="money" /></div>
                        <div class="font-bold text-petrol mt-3">Membresías</div>
                        <div class="text-sm text-gray-500 mt-1">Altas, renovaciones, pagos y vencimientos.</div>
                    </a>
                    <a href="{{ route('configuracion.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="cog" /></div>
                        <div class="font-bold text-petrol mt-3">Configuración</div>
                        <div class="text-sm text-gray-500 mt-1">Seguridad de tu cuenta.</div>
                    </a>
                </div>

            @else
                <!-- Negocio / profesional -->
                <div class="bg-petrol rounded-2xl shadow-card p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-turquoise-500 text-xs font-bold uppercase tracking-widest">{{ $puedeGestionar ? 'Panel del negocio' : 'Mi agenda' }}</p>
                        <h3 class="text-white text-2xl sm:text-3xl font-extrabold mt-1">
                            Buenos días, {{ explode(' ', auth()->user()->name)[0] }}
                        </h3>
                        <p class="text-white/60 text-sm mt-1">Tu negocio, organizado en un solo lugar.</p>
                    </div>
                    <a href="{{ route('agenda.index') }}" class="w-12 h-12 rounded-2xl bg-turquoise-600 hover:bg-turquoise-700 text-white flex items-center justify-center transition" title="Ver agenda">
                        <x-icon name="calendar" class="w-6 h-6" />
                    </a>
                </div>

                @if ($puedeGestionar)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <x-stat title="Citas de hoy" :value="$citasHoy" icon="calendar" />
                        <x-stat title="Pendientes" :value="$pendientes" icon="clock" />
                        <x-stat title="Profesionales" :value="$profesionalesActivos" icon="users" />
                        <x-stat title="Servicios" :value="$serviciosActivos" icon="clipboard" />
                        <x-stat title="Clientes" :value="$clientes" icon="users" />
                    </div>
                @endif

                <!-- Próximas citas -->
                <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                    <div class="flex items-center justify-between p-5 pb-0">
                        <h3 class="text-lg font-bold">Próximas citas</h3>
                        <a href="{{ route('agenda.index') }}" class="text-sm font-semibold text-turquoise-700 hover:underline">Ver agenda →</a>
                    </div>
                    <div class="p-5">
                        @if ($proximas->isEmpty())
                            <x-empty-state icon="calendar" title="Sin citas próximas" message="Las nuevas reservas aparecerán aquí." />
                        @else
                            <ul class="divide-y divide-warm-100">
                                @foreach ($proximas as $cita)
                                    <li class="flex items-center gap-4 py-3">
                                        <div class="w-11 h-11 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center shrink-0">
                                            <x-icon name="clock" class="w-5 h-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-petrol truncate">
                                                {{ \App\Support\FormatoHora::fechaHora($cita->starts_at) }} · {{ $cita->service?->name ?? 'Servicio' }}
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">
                                                {{ $cita->customer_name }} · {{ $cita->professional?->name ?? '' }}
                                            </p>
                                        </div>
                                        <x-badge :status="$cita->status" />
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <!-- Accesos rápidos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('agenda.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="calendar" /></div>
                        <div class="font-bold text-petrol mt-3">Agenda</div>
                        <div class="text-sm text-gray-500 mt-1">Consulta y gestiona las citas del día y la semana.</div>
                    </a>

                    @if ($puedeGestionar)
                        <a href="{{ route('gestion.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="briefcase" /></div>
                            <div class="font-bold text-petrol mt-3">Gestión</div>
                            <div class="text-sm text-gray-500 mt-1">Profesionales, servicios, horarios y festivos.</div>
                        </a>
                    @endif

                    <a href="{{ route('clientes.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="users" /></div>
                        <div class="font-bold text-petrol mt-3">Clientes</div>
                        <div class="text-sm text-gray-500 mt-1">Fichas, historial y consentimiento de datos.</div>
                    </a>

                    @if ($puedeGestionar)
                        <a href="{{ route('reportes.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                            <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="money" /></div>
                            <div class="font-bold text-petrol mt-3">Reportes</div>
                            <div class="text-sm text-gray-500 mt-1">Indicadores, pagos y exportaciones.</div>
                        </a>
                    @endif

                    <a href="{{ route('configuracion.index') }}" class="group bg-white rounded-2xl border border-warm-200 shadow-card p-5 hover:border-turquoise-500 transition">
                        <div class="w-10 h-10 rounded-xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center"><x-icon name="cog" /></div>
                        <div class="font-bold text-petrol mt-3">Configuración</div>
                        <div class="text-sm text-gray-500 mt-1">Política de reservas y seguridad de la cuenta.</div>
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
