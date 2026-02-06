<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cargar FUA Electrónico (Excel)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('fua.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div>
                        <x-input-label for="archivo_excel" :value="__('Seleccionar archivo Excel (.xlsx)')" />
                        <input id="archivo_excel" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 mt-2" type="file" name="archivo_excel" required accept=".xlsx, .xls">
                    </div>

                    <hr>

                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">¿Qué pestañas deseas procesar?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="pestanas[]" value="atencion" checked class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                <span class="text-gray-700">ATENCIÓN - DETALLADO</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="pestanas[]" value="principal_adicional" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                <span class="text-gray-700">PRINCIPAL Y ADICIONAL</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="pestanas[]" value="consumo" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                <span class="text-gray-700">CONSUMO (Medicamentos/Insumos)</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="pestanas[]" value="smi" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                <span class="text-gray-700">SMI (Materno Infantil)</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="pestanas[]" value="reporte_estado" class="form-checkbox h-5 w-5 text-blue-600 rounded">
                                <span class="text-gray-700">REPORTE ESTADO</span>
                            </label>

                        </div>
                        <x-input-error :messages="$errors->get('pestanas')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end">
                        <x-primary-button>
                            {{ __('Iniciar Importación') }}
                        </x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>