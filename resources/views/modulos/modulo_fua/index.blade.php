<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel FUA Electrónico') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="flex justify-end gap-4 mb-4">
    
                <form action="{{ route('fua.destroyAll') }}" method="POST" onsubmit="return confirm('⚠️ ¡PELIGRO! ⚠️\n\n¿Estás seguro de que deseas ELIMINAR TODOS LOS REGISTROS de las tablas FUA?\n\nEsta acción no se puede deshacer.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        🗑️ Vaciar Base de Datos (Boton Temporal de Testeo)
                    </button>
                </form>

                <a href="{{ route('fua.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">
                    Importar Nuevo Excel
                </a>

            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Registros Recientes</h3>
                    <p class="text-sm text-gray-500 mt-2">Aún no has implementado la tabla de listado.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>