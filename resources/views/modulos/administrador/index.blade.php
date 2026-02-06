<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Usuarios y Personal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">¡Éxito!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Listado de Personal Registrado</h3>
                        
                        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Nuevo Usuario
                        </a>
                    </div>

                    <div class="relative overflow-x-auto border rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nombre / Email</th>
                                    <th scope="col" class="px-6 py-3">Documento</th>
                                    <th scope="col" class="px-6 py-3">Rol Asignado</th>
                                    <th scope="col" class="px-6 py-3">Cód. EESS</th>
                                    <th scope="col" class="px-6 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                <tr class="bg-white border-b hover:bg-gray-50 transition">
                                    
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        <div class="text-base font-semibold">{{ $user->name }}</div>
                                        <div class="font-normal text-gray-500">{{ $user->email }}</div>
                                    </th>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <span class="font-bold text-gray-700 mr-1">{{ $user->tipo_doc }}:</span>
                                            <span>{{ $user->num_doc }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @foreach($user->roles as $role)
                                            @php
                                                // Colores según el rol
                                                $color = match($role->name) {
                                                    'admin' => 'bg-red-100 text-red-800 border-red-200',
                                                    'digitador' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    default => 'bg-green-100 text-green-800 border-green-200',
                                                };
                                            @endphp
                                            <span class="{{ $color }} text-xs font-medium mr-2 px-2.5 py-0.5 rounded border">
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @endforeach
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($user->cod_eess)
                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded border border-gray-500">
                                                {{ $user->cod_eess }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">--</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="font-medium text-blue-600 hover:underline mr-3">Editar</a>
                                        
                                        </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No hay usuarios registrados aún.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>