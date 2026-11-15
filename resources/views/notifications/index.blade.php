<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold">Notificaciones</h2>
                <p class="text-sm text-gray-500 mt-0.5">Citas, cambios, errores relevantes e invitaciones.</p>
            </div>
            @if ($sinLeer > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <x-secondary-button>Marcar todas como leídas ({{ $sinLeer }})</x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-2xl border border-warm-200 shadow-card">
                @if ($notificaciones->isEmpty())
                    <x-empty-state icon="bell" title="Sin notificaciones" message="Los avisos de tu negocio aparecerán aquí." />
                @else
                    <ul class="divide-y divide-warm-100">
                        @foreach ($notificaciones as $notificacion)
                            <li class="flex items-start gap-3 p-4 {{ $notificacion->read_at === null ? 'bg-turquoise-100/30' : '' }}">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
                                    {{ ['cita' => 'bg-turquoise-100 text-turquoise-700', 'invitacion' => 'bg-emerald-100 text-emerald-700', 'error' => 'bg-red-100 text-red-700'][$notificacion->type] ?? 'bg-warm-100 text-gray-600' }}">
                                    <x-icon :name="['cita' => 'calendar', 'invitacion' => 'users', 'error' => 'x-circle'][$notificacion->type] ?? 'bell'" class="w-4 h-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-petrol font-semibold">
                                        {{ ['cita' => 'Cita', 'invitacion' => 'Invitación', 'error' => 'Error', 'sistema' => 'Sistema'][$notificacion->type] ?? $notificacion->type }}
                                        <span class="text-xs text-gray-400 font-normal ml-2">{{ $notificacion->created_at->format('d/m/Y H:i') }}</span>
                                    </p>
                                    <p class="text-sm text-gray-600">{{ $notificacion->message }}</p>
                                </div>
                                @if ($notificacion->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notificacion) }}">
                                        @csrf
                                        <x-secondary-button type="submit">Leída</x-secondary-button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="p-4">{{ $notificaciones->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
