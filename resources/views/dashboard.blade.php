<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                
                @role('admin') 
                <a href="{{ route('admin.users.index') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 transition group">
                    <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 group-hover:text-blue-600">
                        ⚙️ Módulo Administrativo
                    </h5>
                    <p class="font-normal text-gray-700">
                        Gestión de Usuarios, Roles y asignación de Establecimientos.
                    </p>
                </a>
                @else
                <div class="block p-6 bg-gray-100 border border-gray-200 rounded-lg shadow opacity-50 cursor-not-allowed">
                    <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-500">⚙️ Módulo Administrativo</h5>
                    <p class="font-normal text-gray-500">Acceso restringido.</p>
                </div>
                @endrole
                
                <a href="{{ route('fua.index') }}" class="block p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-50 transition group">
                    <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 group-hover:text-blue-600">
                        🏥 Módulo FUA Electrónico
                    </h5>
                    <p class="font-normal text-gray-700">
                        Gestión de atenciones, carga masiva y reportes SIS.
                    </p>
                </a>

                

            </div>
            
        </div>
    </div>
</x-app-layout>
