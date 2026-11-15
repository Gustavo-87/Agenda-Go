<section class="space-y-6">
    <header>
        <h3 class="text-lg font-bold text-petrol">Eliminar cuenta</h3>
        <p class="mt-1 text-sm text-gray-500">
            Una vez que tu cuenta sea eliminada, todos sus recursos y datos se eliminarán permanentemente.
            Antes de eliminar tu cuenta, descarga cualquier información o dato que desees conservar.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open', { name: 'confirm-user-deletion' })">
        Eliminar cuenta
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-petrol">
                ¿Estás seguro de que deseas eliminar tu cuenta?
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                Una vez que tu cuenta sea eliminada, todos sus recursos y datos se eliminarán permanentemente.
                Por favor, confirma tu contraseña para continuar.
            </p>

            <div class="mt-4">
                <x-input-label for="password" value="Contraseña" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="Contraseña"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button>
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
