<nav x-data="{ open: false }" class="bg-petrol shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-white font-extrabold text-lg tracking-tight">agenda<span class="text-turquoise-500">Go</span></span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-1 sm:h-full">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Panel') }}
                    </x-nav-link>
                    @if (Auth::user()->isSuperAdmin())
                        <x-nav-link :href="route('admin.pantalla')" :active="request()->routeIs('admin.pantalla')">
                            {{ __('Negocios') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.memberships')" :active="request()->routeIs('admin.memberships')">
                            {{ __('Membresías') }}
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('agenda.index')" :active="request()->routeIs('agenda.index')">
                            {{ __('Agenda') }}
                        </x-nav-link>
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('gestion.index')" :active="request()->routeIs('gestion.index')">
                                {{ __('Gestión') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.index*')">
                            {{ __('Clientes') }}
                        </x-nav-link>
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.index')">
                                {{ __('Reportes') }}
                            </x-nav-link>
                        @endif
                    @endif
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-white/80 hover:text-white hover:bg-white/10 transition">
                            <span class="w-8 h-8 rounded-full bg-turquoise-600 text-white flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                            <x-icon name="arrow-right" class="w-4 h-4 rotate-90 opacity-70" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('configuracion.index')">
                            {{ __('Configuración') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-petrol-900 border-t border-white/10">
        <div class="pt-3 pb-4 px-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="chart">
                {{ __('Panel') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isSuperAdmin())
                <x-responsive-nav-link :href="route('admin.pantalla')" :active="request()->routeIs('admin.pantalla')" icon="briefcase">
                    {{ __('Negocios') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.memberships')" :active="request()->routeIs('admin.memberships')" icon="money">
                    {{ __('Membresías') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('agenda.index')" :active="request()->routeIs('agenda.index')" icon="calendar">
                    {{ __('Agenda') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('gestion.index')" :active="request()->routeIs('gestion.index')" icon="briefcase">
                        {{ __('Gestión') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.index*')" icon="users">
                    {{ __('Clientes') }}
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.index')" icon="money">
                        {{ __('Reportes') }}
                    </x-responsive-nav-link>
                @endif
            @endif
            <x-responsive-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.index')" icon="cog">
                {{ __('Configuración') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-3 pb-4 px-4 border-t border-white/10">
            <div class="text-white font-semibold">{{ Auth::user()->name }}</div>
            <div class="text-white/50 text-sm">{{ Auth::user()->email }}</div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
