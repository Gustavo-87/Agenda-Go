<x-guest-layout>
    <div class="text-center">
        <div class="w-14 h-14 rounded-2xl bg-turquoise-100 text-turquoise-700 flex items-center justify-center mx-auto">
            <x-icon name="shield" class="w-7 h-7" />
        </div>
        <h2 class="mt-4 text-xl font-extrabold text-petrol">Verificación en dos pasos</h2>
        <p class="mt-2 text-sm text-gray-500">
            Ingresa el código que enviamos a tu correo para continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('2fa.verify') }}" class="mt-6">
        @csrf

        <div>
            <x-input-label for="code" value="Código de verificación" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-lg tracking-[0.5em]" type="text" name="code" maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">
                Verificar y continuar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
