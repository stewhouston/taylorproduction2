<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Folder Document') }}
        </h2>
        <div>Estimate #<a href="{{ route('estimate.show', $estimate->id) }}">{{ $estimate->id }}</a></div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-6 py-4 border-t-4 border-blue-600">

                <div class="text-xl font-semibold border-b border-gray-300 mb-4 pb-2">Document Details</div>
            </div>
        </div>
    </div>
</x-app-layout>