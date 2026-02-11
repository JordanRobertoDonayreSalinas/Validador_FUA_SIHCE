<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador SIS 056</title>
    <script src="https://cdn.tailwindcss.com"></script> </head>
<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Simulador Prestacional 056 (MVC)</h2>

        <form action="{{ route('simulador.index') }}" method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol Profesional</label>
                    <select name="profesional" onchange="this.form.submit()" 
                            class="w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Seleccione --</option>
                        <option value="MEDICO" {{ $profesional_seleccionado == 'MEDICO' ? 'selected' : '' }}>Médico</option>
                        <option value="OBSTETRA" {{ $profesional_seleccionado == 'OBSTETRA' ? 'selected' : '' }}>Obstetra</option>
                        <option value="CONSULTOR" {{ $profesional_seleccionado == 'CONSULTOR' ? 'selected' : '' }}>Consultor (300)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Diagnóstico CIE-10</label>
                    <select name="cie10" onchange="this.form.submit()" 
                            class="w-full rounded-md border-gray-300 shadow-sm p-3 border focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Seleccione Diagnóstico --</option>
                        <option value="I10X" {{ $cie10_busqueda == 'I10X' ? 'selected' : '' }}>I10X - Hipertensión</option>
                        <option value="E119" {{ $cie10_busqueda == 'E119' ? 'selected' : '' }}>E119 - Diabetes T2</option>
                        <option value="D509" {{ $cie10_busqueda == 'D509' ? 'selected' : '' }}>D509 - Anemia</option>
                        <option value="J00X" {{ $cie10_busqueda == 'J00X' ? 'selected' : '' }}>J00X - Resfriado</option>
                        <option value="Z359" {{ $cie10_busqueda == 'Z359' ? 'selected' : '' }}>Z359 - Emb. Alto Riesgo</option>
                    </select>
                </div>
            </div>
        </form>

        @if($diagnostico_obj || $profesional_seleccionado)
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Análisis de Reglas</h3>

                {{-- Sección de Alertas --}}
                @if(count($alertas) > 0)
                    <div class="mb-4">
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                            <p class="font-bold">Observaciones:</p>
                            <ul class="list-disc ml-5">
                                @foreach($alertas as $alerta)
                                    <li>{{ $alerta }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- SECCIÓN NUEVA: PROTOCOLOS ASOCIADOS --}}
                @if(count($protocolos_sugeridos) > 0)
                    <div class="mt-6">
                        <h4 class="text-md font-bold text-gray-800 mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Protocolo Sugerido (CPMS y Medicamentos)
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg text-sm">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Tipo</th>
                                        <th class="py-2 px-4 text-left">Código</th>
                                        <th class="py-2 px-4 text-left">Descripción</th>
                                        <th class="py-2 px-4 text-center">Regla</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($protocolos_sugeridos as $item)
                                        <tr class="{{ $item->es_obligatorio ? 'bg-red-50' : '' }}">
                                            <td class="py-2 px-4 font-semibold text-xs">
                                                <span class="px-2 py-1 rounded {{ $item->tipo == 'LABORATORIO' ? 'bg-blue-100 text-blue-800' : ($item->tipo == 'MEDICAMENTO' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                                    {{ $item->tipo }}
                                                </span>
                                            </td>
                                            <td class="py-2 px-4">{{ $item->codigo_item }}</td>
                                            <td class="py-2 px-4">{{ $item->descripcion }}</td>
                                            <td class="py-2 px-4 text-center">
                                                @if($item->es_obligatorio)
                                                    <span class="text-xs font-bold text-red-600 border border-red-200 bg-white px-2 py-0.5 rounded shadow-sm">
                                                        {{ $item->regla_asociada }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500">{{ $item->regla_asociada }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 text-right">* Los items en rojo son de registro obligatorio según normativa vigente.</p>
                    </div>
                @endif

                {{-- Sección de Obligatorios --}}
                @if(count($obligatorios) > 0)
                    <div class="mb-4">
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                            <p class="font-bold">Requisitos Obligatorios (SMI/RC):</p>
                            <ul class="list-disc ml-5">
                                @foreach($obligatorios as $req)
                                    <li>{{ $req }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Info Técnica --}}
                @if($diagnostico_obj)
                    <div class="mt-4 text-sm text-gray-500 border-t pt-2">
                        <span class="font-semibold">Diagnóstico seleccionado:</span> {{ $diagnostico_obj->descripcion }}
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-10 text-gray-400">
                <p>Seleccione un profesional y un diagnóstico para ver las reglas.</p>
            </div>
        @endif
    </div>

</body>
</html>