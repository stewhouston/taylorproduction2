<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Custom Document') }}
        </h2>
        <div>Estimate #<a href="{{ route('estimate.show', $estimate->id) }}">{{ $estimate->id }}</a></div>
    </x-slot>

    <div class="py-8">
        <form class="max-w-7xl mx-auto sm:px-6 lg:px-8" method="POST" action="{{ route('estimate.document.store', $estimate->id) }}">
            @csrf
            <input type="hidden" name="document_type_id" value="{{$document_type->id}}">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-6 py-4 border-t-4 border-blue-600">

                <h3 class="text-xl font-semibold border-b border-gray-300 mb-4 pb-2">Document Details</h3>

                <div class="flex mb-4 items-center{{ $errors->has('quantity') ? ' has-error' : '' }}">
                    <label for="quantity" class="mr-4 font-semibold w-[220px]">Quantity *</label>
                    <div class="col-sm-9">
                        <input id="quantity" name="quantity" type="number" min=1 class="form-control" required value="{{ old('quantity') }}">
                        @if ($errors->has('quantity'))
                        <span class="help-block">
                            <strong>{{ $errors->first('quantity') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('name') ? ' has-error' : '' }}">
                    <label for="name" class="mr-4 font-semibold w-[220px]">Name *</label>
                    <div class="col-sm-9">
                        <select required id="name" name="name" class="w-[500px]">
                            <option value=""></option>
                            @foreach($document_type_names as $dtname)
                            <option value="{{ $dtname->short }}">{{ $dtname->name }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('description') ? ' has-error' : '' }}">
                    <label for="description" class="mr-4 font-semibold w-[220px]">Description</label>
                    <div class="col-sm-9">
                        <input id="description" name="description" type="text" class="w-[500px]" value="{{ old('description') }}">
                        @if ($errors->has('description'))
                        <span class="help-block">
                            <strong>{{ $errors->first('description') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('specification') ? ' has-error' : '' }}">
                    <label for="specification" class="mr-4 font-semibold w-[220px]">Specification</label>
                    <div class="col-sm-9">
                        <textarea id="specification" name="specification" style="min-height: 200px;" class="w-[500px]" value="{{ old('specification') }}"></textarea>
                        @if ($errors->has('specification'))
                        <span class="help-block">
                            <strong>{{ $errors->first('specification') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <hr class="mb-4" />

                @if ($current_user->isAdmin())
                <div class="flex mb-4 items-center">
                    <input type="checkbox" name="is_digital_only" value="1" id="is_digital_only" />
                    <label for="is_digital_only" class="text-purple-700 italic ml-2">Digital Only</label>
                </div>
                @endif

                <div class="flex items-center">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 mr-4">Submit</button>
                    <a class="text-blue-800" href="{{route('estimate.show', $estimate->id)}}">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
    (function(w) {
        w.taylor.pageMode = 'CREATE';
        w.taylor.documentType = 'custom';
    })(window);
    </script>
    <script type="text/javascript" src="/js/taylor-document.js"></script>
</x-app-layout>