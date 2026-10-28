<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Multi Sheet Document') }}
        </h2>
        <div>Estimate #<a href="{{ route('estimate.show', $estimate->id) }}">{{ $estimate->id }}</a></div>
    </x-slot>

    <div class="py-8">
        <form class="max-w-7xl mx-auto sm:px-6 lg:px-8" method="POST" action="{{ route('estimate.document.store', $estimate->id) }}">
            @csrf
            <input type="hidden" name="document_type_id" value="{{$document_type->id}}" />
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-6 py-4 border-t-4 border-blue-600">

                <h3 class="text-xl font-semibold border-b border-gray-300 mb-4 pb-2">Document Details</h3>

                <div class="flex mb-4 items-center{{ $errors->has('size') ? ' has-error' : '' }}">
                    <label for="size" class="mr-4 font-semibold w-[220px]">Size *</label>
                    <div class="col-sm-9">
                        <select id="size" name="size" class="form-control">
                            @foreach($document_type->sizes as $size)
                            <option value="{{$size->name}}">{{$size->name}}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('size'))
                        <span class="help-block">
                            <strong>{{ $errors->first('size') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('finish') ? ' has-error' : '' }}">
                    <label for="finish" class="mr-4 font-semibold w-[220px]">Finish *</label>
                    <div class="col-sm-9">
                        <select id="finish" name="finish" class="form-control">
                            <option value="Corner Stitch" selected> Corner Stitch</option>
                            <option value="No Finishing" > No Finishing</option>
                        </select>
                        @if ($errors->has('finish'))
                        <span class="help-block">
                            <strong>{{ $errors->first('finish') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

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

                <h3 class="text-xl font-semibold border-b border-gray-300 mt-8 mb-4 pb-2">Text Details</h3>

                <div class="flex mb-4 items-center{{ $errors->has('text_stock') ? ' has-error' : '' }}">
                    <label for="text_stock" class="mr-4 font-semibold w-[220px]">Stock *</label>
                    <div class="col-sm-9">
                        <select id="text_stock" name="text_stock" class="form-control">
                            <option value="20lb Bond" selected> 20lb Bond</option>
                            <option value="24lb Bond"> 24lb Bond</option>
                            <option value="80lb Ctd Text"> 80lb Ctd Text</option>
                            <option value="100lb Ctd Text"> 100lb Ctd Text</option>
                            <option value="20lb Bond Colours"> 20lb Bond Colours</option>
                        </select>
                        @if ($errors->has('text_stock'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_stock') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('print_side1') ? ' has-error' : '' }}">
                    <label for="print_side1" class="mr-4 font-semibold w-[220px]">Print Side 1 *</label>
                    <div class="col-sm-9">
                        <select id="print_side1" name="print_side1" class="form-control">
                            <option value="Black"> Black</option>
                            <option value="Colour"> Colour</option>
                        </select>
                        @if ($errors->has('print_side1'))
                        <span class="help-block">
                            <strong>{{ $errors->first('print_side1') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('print_side2') ? ' has-error' : '' }}">
                    <label for="print_side2" class="mr-4 font-semibold w-[220px]">Print Side 2 *</label>
                    <div class="col-sm-9">
                        <select id="print_side2" name="print_side2" class="form-control">
                            <option value="none"> None</option>
                            <option value="Black"> Black</option>
                            <option value="Colour"> Colour</option>
                        </select>
                        @if ($errors->has('print_side2'))
                        <span class="help-block">
                            <strong>{{ $errors->first('print_side2') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('bleed') ? ' has-error' : '' }}">
                    <label for="bleed" class="mr-4 font-semibold w-[220px]">Bleed</label>
                    <div class="col-sm-9">
                        <select id="bleed" name="bleed" class="form-control">
                            <option value="0"> No</option>
                        </select>
                        @if ($errors->has('bleed'))
                        <span class="help-block">
                            <strong>{{ $errors->first('bleed') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('text_pages') ? ' has-error' : '' }}">
                    <label for="text_pages" class="mr-4 font-semibold w-[220px]">Pages *</label>
                    <div class="col-sm-9">
                        <input id="text_pages" name="text_pages" type="number" class="form-control" required value="{{ old('text_pages', '2') }}" required min="2" max="400">
                        @if ($errors->has('text_pages'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_pages') }}</strong>
                        </span>
                    @endif
                    </div>
                </div>

                <hr class="mb-4" />

                @if ($current_user->isAdmin())
                <div class="flex items-center">
                    <input type="checkbox" name="is_digital_only" value="1" id="is_digital_only" />
                    <label for="is_digital_only" class="text-purple-700 italic ml-1">Digital Only</label>
                </div>
                @endif

                <div class="mt-8">
                    <div class="flex items-center">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 mr-4">Submit</button>
                        <a class="text-blue-800 underline" href="{{route('estimate.show', $estimate->id)}}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
    (function(w) {
        w.taylor.pageMode = 'CREATE';
        w.taylor.documentType = 'multi-sheet';
    })(window);
    </script>
    <script type="text/javascript" src="/js/taylor-document.js"></script>
</x-app-layout>