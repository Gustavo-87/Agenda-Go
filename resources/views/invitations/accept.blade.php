<x-guest-layout>
    <div class="text-center">
        <div class="w-14 h-14 rounded-2xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center mx-auto">
            <x-icon name="users" class="w-7 h-7" />
        </div>
        <h2 class="mt-4 text-xl font-extrabold text-petrol">Únete a {{ $invitation->business?->name }}</h2>
        <p class="mt-2 text-sm text-gray-500">
            Invitación para <span class="font-semibold">{{ $invitation->email }}</span>.
            Crea tu contraseña para activar la cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('invitations.accept.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ request()->query('token') }}">

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
        </div>

        <x-primary-button class="w-full justify-center">Activar mi cuenta</x-primary-button>
    </form>
</x-guest-layout>
