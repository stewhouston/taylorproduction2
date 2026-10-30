<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Saddlestitch Document') }}
        </h2>
        <div>Estimate #<a href="{{ route('estimate.show', $estimate->id) }}">{{ $estimate->id }}</a></div>
    </x-slot>

    <div class="py-8">
        <form class="max-w-7xl mx-auto sm:px-6 lg:px-8" method="POST" action="{{ route('estimate.document.store', $estimate->id) }}">
            {{ csrf_field() }}
            <input type="hidden" name="document_type_id" value="{{$document_type->id}}">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-6 py-4 border-t-4 border-blue-600">

                <h3 class="text-xl font-semibold border-b border-gray-300 mb-4 pb-2">Document Details</h3>

                <div class="flex mb-4 items-center{{ $errors->has('size') ? ' has-error' : '' }}">
                <label for="size" class="mr-4 font-semibold w-[220px]">Size *</label>
                <div class="col-sm-9">
                    <select id="size" name="size" class="form-control">
                    <option value="8 1/2 x 11">8 1/2 x 11</option>
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
                    <option value="Saddlestitch">Saddlestitch</option>
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
                    <input id="quantity" name="quantity" type="number" min="1" class="form-control"
                    required value="{{ old('quantity') }}">
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

                <div class="flex mb-4 items-center{{ $errors->has('cover_type') ? ' has-error' : '' }}">
                <label for="description" class="mr-4 font-semibold w-[220px]">Cover</label>
                <div class="col-sm-9">
                    <select id="cover_type" name="cover_type" class="form-control">
                    <option value="self" {{ old('cover_type') == "self" ? 'selected' : '' }}>Self</option>
                    <option value="plus" {{ old('cover_type') == "plus" ? 'selected' : '' }}>Plus</option>
                    </select>
                    @if ($errors->has('cover_type'))
                    <span class="help-block">
                        <strong>{{ $errors->first('cover_type') }}</strong>
                    </span>
                    @endif
                </div>
                </div>

                <h3 class="text-xl font-semibold border-b border-gray-300 mt-8 mb-4 pb-2">Plus Cover Details</h3>

                <div id="group-plus-cover-details">
                    <div class="flex mb-4 items-center{{ $errors->has('cover_stock') ? ' has-error' : '' }}">
                        <label for="cover_stock" class="mr-4 font-semibold w-[220px]">Stock *</label>
                        <div class="col-sm-9">
                            <select id="cover_stock" name="cover_stock" class="form-control">
                                <option value="none" selected> None</option>
                                <option value="20lb Bond"> 20lb Bond</option>
                                <option value="24lb Bond"> 24lb Bond</option>
                                <option value="100lb Text"> 100lb Text</option>
                                <option value="80lb Ctd Text"> 80lb Ctd Text</option>
                                <option value="100lb Ctd Text"> 100lb Ctd Text</option>
                                <option value="8pt Ctd Cover"> 8pt Ctd Cover</option>
                                <option value="10pt Ctd Cover"> 10pt Ctd Cover</option>
                                <option value="8pt Cover"> 8pt Cover</option>
                            </select>
                            @if ($errors->has('cover_stock'))
                            <span class="help-block">
                                <strong>{{ $errors->first('cover_stock') }}</strong>
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex mb-4 items-center{{ $errors->has('print_side1') ? ' has-error' : '' }}">
                        <label for="print_side1" class="mr-4 font-semibold w-[220px]">Print Side 1 *</label>
                        <div class="col-sm-9">
                            <select id="print_side1" name="print_side1" class="form-control">
                                <option value="none" selected> None</option>
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
                        <label for="print_side2" class="mr-4 font-semibold w-[220px]">Print Side 2</label>
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
                                <option value="1"> Yes</option>
                            </select>
                            @if ($errors->has('bleed'))
                            <span class="help-block">
                                <strong>{{ $errors->first('bleed') }}</strong>
                            </span>
                            @endif
                        </div>
                    </div>
                </div><!--#group-plus-cover-details-->

                <h3 class="text-xl font-semibold border-b border-gray-300 mt-8 mb-4 pb-2">Text Details</h3>

                <div class="flex mb-4 items-center{{ $errors->has('text_stock') ? ' has-error' : '' }}">
                    <label for="text_stock" class="mr-4 font-semibold w-[220px]">Stock *</label>
                    <div class="col-sm-9">
                        <select id="text_stock" name="text_stock" class="form-control">
                            <option value="20lb Bond" selected> 20lb Bond</option>
                            <option value="24lb Bond"> 24lb Bond</option>
                            <option value="80lb Ctd Text"> 80lb Ctd Text</option>
                            <option value="20lb Bond Colours"> 20lb Bond Colours</option>
                        </select>
                        @if ($errors->has('text_stock'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_stock') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('text_print') ? ' has-error' : '' }}">
                    <label for="text_print" class="mr-4 font-semibold w-[220px]">Printed *</label>
                    <div class="col-sm-9">
                        <select id="text_print" name="text_print" class="form-control">
                            <option value="Black"> Black</option>
                            <option value="Colour"> Colour</option>
                        </select>
                        @if ($errors->has('text_print'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_print') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('text_bleed') ? ' has-error' : '' }}">
                    <label for="text_bleed" class="mr-4 font-semibold w-[220px]">Bleed</label>
                    <div class="col-sm-9">
                        <select id="text_bleed" name="text_bleed" class="form-control">
                            <option value="0"> No</option>
                        </select>
                        @if ($errors->has('text_bleed'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_bleed') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>

                <div class="flex mb-4 items-center{{ $errors->has('text_pages') ? ' has-error' : '' }}">
                    <label for="text_pages" class="mr-4 font-semibold w-[220px]">Pages (including cover) *</label>
                    <div class="col-sm-9">
                        <input id="text_pages" name="text_pages" type="number" class="form-control" required value="{{ old('text_pages', '8') }}" required min="8" max="148">
                        @if ($errors->has('text_pages'))
                        <span class="help-block">
                            <strong>{{ $errors->first('text_pages') }}</strong>
                        </span>
                        @endif
                    </div>
                </div>

                <hr class="mb-4" />

                <div class="flex mb-4 items-center{{ $errors->has('is_sequential') ? ' has-error' : '' }}">
                <label class="mr-4 font-semibold w-[220px]">Other:</label>
                <div class="col-lg-9">
                    <div class="flex items-center">
                    <div style="white-space: nowrap;">
                        <input type="checkbox" id="is_sequential" name="is_sequential" value="1"
                            {{ old('is_sequential') ? 'checked' : '' }}
                        />
                        <label for="is_sequential" style="padding-left: 6px; font-weight: normal;">
                        <span style="margin-right: 4px;">Seq. Number</span>
                        <span>(Red)</span>
                        </label>
                    </div>
                    <!-- Visibility: cover_type = 'plus' -->
                    <div id="form-group-count-imprints"
                        class="flex" style="margin-left: 30px;"> 
                        <input id="count_imprints" name="count_imprints" type="number" min="0" max="4294967295"
                            class="mr-2" style="width: 68px;"
                            value="{{ old('count_imprints') ?: 0 }}"
                        >
                        <label for="count_imprints" class="text-left font-normal flex items-center">
                        <span>Dealer Imprints</span>
                        <span>(Versions)</span>
                        </label>
                    </div>
                    <div id="form-group-count-color-inserts"
                        class="flex" style="margin-left: 30px;"
                    >
                        <input id="count_color_inserts" name="count_color_inserts" type="number" min="0" max="4294967295"
                            class="mr-2" style="width: 68px;"
                            value="{{ old('count_color_inserts') ?: 0 }}"
                        >
                        <label for="count_color_inserts" class="text-left font-normal flex items-center">
                        <span style="margin-right: 6px;">Colour Pages</span> 
                        <span>(inserts)</span>
                        </label>
                    </div>
                    </div>
                </div>
                </div>

                @if ($current_user->isAdmin())
                <div class="flex mb-4 items-center" style="margin-top: -18px;">
                <label class="mr-4 font-semibold w-[220px]">&nbsp;</label>
                <div class="col-lg-6 ty-cb-column">
                    <input type="checkbox" name="is_digital_only" value="1" id="is_digital_only" />
                    <label for="is_digital_only" class="text-purple-700 italic">Digital Only</label>
                </div>
                </div>
                @endif

                <div class="mt-8">
                    <div class="flex items-center">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 mr-4">Submit</button>
                        <a class="text-blue-800" href="{{route('estimate.show', $estimate->id)}}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
    (function(w) {
        w.taylor.pageMode = 'CREATE';
        w.taylor.documentType = 'saddlestitch';
    })(window);
    </script>
    <script type="text/javascript" src="/js/taylor-document.js"></script>
</x-app-layout>