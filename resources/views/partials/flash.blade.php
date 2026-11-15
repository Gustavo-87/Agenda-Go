@if (session('success'))
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
            <x-icon name="check" class="w-5 h-5 text-emerald-600" />
            {{ session('success') }}
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl text-sm shadow-sm">
            <div class="flex items-center gap-2 font-semibold mb-1">
                <x-icon name="x-circle" class="w-5 h-5 text-red-600" />
                Revisa los siguientes campos:
            </div>
            <ul class="list-disc ms-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
