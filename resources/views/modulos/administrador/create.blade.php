<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Nuevo Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <x-input-label for="tipo_doc" :value="__('Tipo Doc.')" />
                                <select id="tipo_doc" name="tipo_doc" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach($tipos_doc as $tipo)
                                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tipo_doc')" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="num_doc" :value="__('Número de Documento')" />
                                <x-text-input id="num_doc" class="block mt-1 w-full" type="text" name="num_doc" :value="old('num_doc')" required autofocus />
                                <x-input-error :messages="$errors->get('num_doc')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <x-input-label for="nombres" :value="__('Nombres')" />
                                <x-text-input id="nombres" class="block mt-1 w-full uppercase" type="text" name="nombres" :value="old('nombres')" required />
                                <x-input-error :messages="$errors->get('nombres')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="apellido_paterno" :value="__('Apellido Paterno')" />
                                <x-text-input id="apellido_paterno" class="block mt-1 w-full uppercase" type="text" name="apellido_paterno" :value="old('apellido_paterno')" required />
                                <x-input-error :messages="$errors->get('apellido_paterno')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="apellido_materno" :value="__('Apellido Materno')" />
                                <x-text-input id="apellido_materno" class="block mt-1 w-full uppercase" type="text" name="apellido_materno" :value="old('apellido_materno')" required />
                                <x-input-error :messages="$errors->get('apellido_materno')" class="mt-2" />
                            </div>
                        </div>

                        <hr class="my-6 border-gray-200">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="email" :value="__('Correo Electrónico')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="cod_eess" :value="__('Código EESS (Establecimiento)')" />
                                <x-text-input id="cod_eess" class="block mt-1 w-full" type="text" name="cod_eess" :value="old('cod_eess')" placeholder="Ej: 00003361" />
                                <p class="text-xs text-gray-500 mt-1">Este código vinculará al usuario con sus FUAs.</p>
                                <x-input-error :messages="$errors->get('cod_eess')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <x-input-label for="password" :value="__('Contraseña')" />
                                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirmar Contraseña')" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="role" :value="__('Asignar Rol')" />
                                <select id="role" name="role" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                    <option value="">-- Seleccionar Rol --</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-4">
                                {{ __('Cancelar') }}
                            </a>
                            
                            <x-primary-button>
                                {{ __('Guardar Usuario') }}
                            </x-primary-button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>