<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Estimate') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                Create Estimate
            </div>
        </div>
    </div>

    @section('scripts')
    @parent
        <script src="/js/jquery.inputmask.js"></script>
        <script src="/js/jquery.inputmask.date.extensions.js"></script>
        <script src="/js/jquery.inputmask.extensions.js"></script>
    @endsection
</x-app-layout>