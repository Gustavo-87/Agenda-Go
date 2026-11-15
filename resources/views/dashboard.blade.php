<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel principal') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($misCitasHoy->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Mis citas de hoy</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-4">Hora</th>
                                        <th class="py-2 pr-4">Cliente</th>
                                        <th class="py-2 pr-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($misCitasHoy as $cita)
                                        <tr>
                                            <td class="py-2 pr-4">{{ substr($cita->starts_at, 11, 5) }}</td>
                                            <td class="py-2 pr-4">{{ $cita->customer_name }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cita->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ $cita->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()->isSuperAdmin())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ([
                        ['Negocios', $negociosTotal],
                        ['Negocios activos', $negociosActivos],
                        ['Membresías por vencer', $membresiasPorVencer],
                        ['Citas hoy', $citasHoy],
                    ] as [$titulo, $valor])
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <div class="text-sm text-gray-500">{{ $titulo }}</div>
                            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $valor }}</div>
                        </div>
                    @endforeach
                </div>
            @elseif (isset($citasHoy))
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach ([
                        ['Citas hoy', $citasHoy],
                        ['Pendientes', $pendientes],
                        ['Profesionales', $profesionalesActivos],
                        ['Servicios', $serviciosActivos],
                        ['Clientes', $clientes],
                    ] as [$titulo, $valor])
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <div class="text-sm text-gray-500">{{ $titulo }}</div>
                            <div class="text-3xl font-bold text-gray-900 mt-1">{{ $valor }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('agenda.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="font-semibold text-gray-800">Agenda</div>
                    <div class="text-sm text-gray-500 mt-1">Consulta y gestiona las citas del día.</div>
                </a>

                @if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('gestion.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                        <div class="font-semibold text-gray-800">Gestión</div>
                        <div class="text-sm text-gray-500 mt-1">Profesionales, servicios, horarios y festivos.</div>
                    </a>
                @endif

                <a href="{{ route('clientes.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="font-semibold text-gray-800">Clientes</div>
                    <div class="text-sm text-gray-500 mt-1">Fichas, historial y consentimiento de datos.</div>
                </a>

                @if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <a href="{{ route('reportes.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                        <div class="font-semibold text-gray-800">Reportes</div>
                        <div class="text-sm text-gray-500 mt-1">Indicadores, pagos y exportaciones.</div>
                    </a>
                @endif

                <a href="{{ route('configuracion.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition">
                    <div class="font-semibold text-gray-800">Configuración</div>
                    <div class="text-sm text-gray-500 mt-1">Política de reservas y seguridad de la cuenta.</div>
                </a>

                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.pantalla') }}" class="block bg-teal-700 text-white shadow-sm sm:rounded-lg p-6 hover:bg-teal-800 transition">
                        <div class="font-semibold">Administración SaaS</div>
                        <div class="text-sm text-teal-100 mt-1">Negocios, membresías y pagos.</div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
