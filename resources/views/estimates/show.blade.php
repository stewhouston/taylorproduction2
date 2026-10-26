<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    {{ __('Estimate') . ' #' . $estimate->id }} ({{ $estimate->company->name }})
                </h2>
                <div class="text-lg">
                    <span class="font-semibold">Status:</span> <span class="italic">{{ !empty($estimate->status) ? ucfirst(str_replace('_', ' ', $estimate->status)) : 'Active' }}</span>
                </div>
            </div>
            <div>
                <button
                    class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                >Mark as Print Order</button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="lg:px-8 xl:w-full xl:mx-4 2xl:max-w-[80%] 2xl:mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">
                @if (!auth()->user()->isReadOnly())
                    <section class="content-header">
                        <ul id="taylor-button-nav-bar" class="nav-horizontal text-center {{ $estimate->is_print_order ? 'nav-as-print-order' : '' }} {{ $estimate->has_alert_disable_interact ? 'nav-has-alert-disable-interact' : '' }}">
                        <li style="float: left; pointer-events: all; opacity: 1; filter: saturate(1)">
                            <a href="{{route('estimates.index')}}{{ $estimate->is_archived ? '?archives=1' : '' }}">
                            <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </li>

                        <li class="{{ $estimate->has_rush_fee ? 'active' : '' }}">
                            <a href="#" id="toggle-rush-fee"><i class="fa fa-bolt"></i> Rush Fee</a>
                        </li>
                        <li>
                            <a href="{{route('estimate.edit', $estimate->id)}}"><i class="fa fa-edit"></i> Edit Estimate</a>
                        </li>
                        <li>
                            <a id="btn-delete-estimate" href="{{route('estimate.destroy', $estimate->id)}}"><i class="fa fa-remove"></i> Delete</a>
                        </li>
                        <!--<li>
                            <a href="#" id="est-button-duplicate"><i class="fa fa-copy"></i> Duplicate</a>
                        </li>-->
                        <li class="{{ $estimate->is_archived ? 'active' : '' }}">
                            <a href="#" id="btn-archive-estimate"><i class="fa fa-archive"></i> Archive</a>
                        </li>
                        <li>
                            <a style="cursor: pointer;" onclick="window.open('{{ route("estimate.print", $estimate->id) }}','name','width=800,height=920'); return false;"><i class="fa fa-print"></i> Print</a>
                        </li>
                        <li>
                            <a href="{{route('shipping.drops', $estimate->id)}}" id="shipping-button">
                            <i class="fa fa-truck"></i> Shipping
                            </a>
                        </li>
                        <li>
                            <a href="#"  id="btn-add-document">
                            <i class="fa fa-book"></i> Add Document
                            </a>
                        </li>
                        </ul>
                    </section>
                @endif

                <div class="border-t border-solid border-gray-300 mt-4 pt-4">
                    <div class="flex justify-between">
                        <div>
                            <div><span class="font-semibold">Number:</span> <span>{{$estimate->client_reference_number}}</span></div>
                            <div><span class="font-semibold">Description:</span> <span>{{$estimate->client_reference_description}}</span></div>
                            <div><span class="font-semibold">Mail Date:</span> <span id="mail-date-text-value">{{$estimate->client_reference_mail_date}}</span></div>
                            <div><span class="font-semibold">Files Required By:</span> <span id="files-required-by">—</span></div>
                            <div>
                            @if ($estimate->file_require_adjust_reason)
                                <div style="color: #666; font-style: italic; font-size: 13px; margin-bottom: 4px;">
                                    {{ $estimate->file_require_adjust_reason }}
                                </div>
                                @else
                                <div style="color: #666; font-style: italic; font-size: 13px; margin-bottom: 4px;">
                                    Rush fee applies to files arriving after file required date time.
                                </div>
                            @endif
                            </div>
                        </div>
                        <div>
                            <div><span class="font-semibold">Created By:</span> todo</div>
                            <div><span class="font-semibold">Estimate Last Modified By:</span> todo</div>
                            <div><span class="font-semibold">Shipping Last Modified By:</span> todo</div>
                            <div><span class="font-semibold">Adjustments Last Modified By:</span> todo</div>
                        </div>
                    </div>
                </div>

                <ul class="flex mt-4 text-lg">
                    <li class="mr-4"><a class="font-bold text-blue-800" href="#documents-{{ $estimate->id }}" id="btn-toggle-documents"><i class="fa fa-file-text-o"></i> Documents</a></li>
                    @if (request()->user()->hasPermissions('debug'))
                    <li><a href="#debug-{{ $estimate->id }}" id="btn-toggle-debug" class="text-blue-800"><i class="fa fa-bug"></i> Debug</a></a></li>
                    @endif
                </ul>

                <div>
                    <div id="documents-container">
                        <table class="w-full mt-4" cellpadding="4" cellspacing="4">
                        <thead>
                        <tr class="bg-gray-800 text-white ">
                            <!--<th></th>-->
                            <th>QTY</th>
                            <th style="width: 50%;" class="text-left">Description</th>
                            <th class="text-left">Cost</th>
                            <th class="print-action-column text-left">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $key => $document)
                            <tr class="hover:bg-gray-100">
                            <!--<td><button class="btn btn-default btn-xs" data-target="#document{{$key}}" data-toggle="collapse"><span class="glyphicon glyphicon-eye-open"></span></button></td>-->
                            <td class="text-center align-top">{{number_format($document->quantity)}}</td>
                            <td>
                                @if ($document->is_pending)
                                <a style="color: hsl(0, 71%, 57%);">PENDING</a> -
                                @endif
                                {{$document->report_description}}
                                @if (!empty($document->specification))
                                <span style="font-size: 80%">(<a class="toggle-specification" href="#">Show User Specification</a>)</span>
                                <pre class="specification-embed-container">{{ trim($document->specification) }}</pre>
                                @endif
                                <div style="padding-left: 30px;">
                                    @foreach($document->components as $component)
                                        <div>&bull; {{$component->report_description}}</div>
                                    @endforeach
                                    @if($document->production && $document->production->custom_description_1)
                                        <div>&bull; {{$document->production->custom_description_1}}</div>
                                    @endif
                                    @if($document->production && $document->production->custom_description_2)
                                        <div>&bull; {{$document->production->custom_description_2}}</div>
                                    @endif
                                        <div>&bull; Weight: {{ $document->production->weight ?? '&mdash;' }}lbs |
                                        @if ($document->production && $document->production->weight)
                                        {{ round($document->production->weight * 453.592, 0) }}g
                                        @endif</div>
                                    @if (request()->user()->hasPermissions('estimate_admin') && !empty($document->production))
                                    <div>
                                        Production Timing: {{ $document->production->hour_range}} hours
                                        {!! $document->is_digital_only ? ' | <span class="estimate-tag-digital-only">Digital Only</span>' : '' !!}
                                    </div>
                                    @endif
                                    @if (!empty($document->getDisplayMessages()))
                                    <div class="document-display-messages-container">
                                        @foreach ($document->getDisplayMessages() as $document_message)
                                        <div class="document-message">{{ $document_message }}</div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="cost-cell" style="width: 160px;">
                            @if(!empty($document->production))
                                <a class="cost-text">
                                {{number_format($document->production->sell, 2)}}
                                </a>
                                <div class="cost-details">
                                Bind: {{number_format($document->production->bind_sell, 2)}}<br/>
                                Print: {{number_format($document->production->sell - $document->production->bind_sell, 2)}}
                                </div>
                            @else
                                &ndash;
                            @endif
                            </td>
                            <td class="print-action-column">
                            @if (!auth()->user()->isReadOnly() && !$estimate->has_alert_disable_interact)
                            <form method="POST" action="#" role="role" class="form-inline">
                                {{csrf_field()}}
                                <span class="user-action-controls" style="display: inline-block;">
                                <input name="_method" type="hidden" value="DELETE">
                                @if($document->is_custom && auth()->user()->isAdmin() && auth()->user()->hasPermissions('price_management'))
                                <a class="btn btn-link btn-set-custom-parameters" data-document-id="{{$document->id}}" style="font-weight: bold; padding: 0;">Estimate Detail</a> |
                                @endif
                                <a type="button" class="btn btn-link btn-edit-estimate" style="padding:0" href="#">
                                Edit</a>
                                | <button type="submit" class="btn btn-link btn-delete-document" style="padding:0">Delete</button>
                                </span>
                                <span class="print-order-notifier" style="display: none; color: #999; font-style: italic;">Print Order</span>
                                @if (request()->user()->hasPermissions('estimate_admin'))
                                | <a class="btn btn-link btn-production-details"
                                        style="padding:0;"
                                        data-document-id="{{$document->id}}"
                                        data-estimate-id="{{$document->estimate->id}}"
                                >Production Details</a>
                                @endif
                            </form>
                            @else
                            &ndash;
                            @endif
                            </td>
                            </tr>
                            <tr style="display: none;">
                            <td colspan="12" class="hiddenRow">
                                <div class="accordian-body collapse" id="document{{$key}}">
                                <table class="table">
                                    <thead>
                                    <tr style="font-weight:bold">
                                        <td>Components</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($document->components as $component)
                                    <tr>
                                        <td>{{$component->report_description}}</td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                </div>
                            </td>
                            </tr>

                            @endforeach
                        </tbody>
                        <tfoot>
                            @if (request()->user()->hasPermissions('estimate_admin') && $documents->count() > 0)
                            <tr>
                                <td colspan="3">&nbsp;</td>
                                <td>

                                    <a class="btn btn-link btn-production-details all"
                                        style="padding:0;"
                                        data-document-id="all"
                                        data-estimate-id="{{$estimate->id}}"
                                    >Production Details (All)</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">&nbsp;</td>
                                <td>
                                    <button class="btn btn-link btn-adjustments-handle"
                                        style="padding:0;"
                                        data-document-id="all"
                                        data-estimate-id="{{$estimate->id}}"
                                    >Adjustments</button>
                                </td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                    @if ($documents->count() === 0)
                        <div class="mt-2">This estimate has no documents</div>
                    @endif
                    </div><!--#documents-container-->
                    <div id="debug-container" class="hidden">
                        <table class="">
                        <tbody>
                            @foreach($documents as $key => $document)
                            <tr class="debug_row_{{$key}}">
                            <td>
                                {{$document}}
                            </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tbody>
                        </table>
                    </div><!--#debug-container-->
                </div>
            </div>
        </div>
    </div>

    <div id="add-document-menu" 
        class="bg-gray-800 text-white absolute top-0 mt-8 rounded-md"
    >
        <a class="absolute p-2 cursor-pointer" style="right: 12px; top: 10px;" id="btn-close-add-document">x</a>
        <h3 class="font-semibold text-xl px-4 py-4">Add Document</h3>
        @foreach ($document_types as $document_type)
            <ol>
                <li><a class="block px-4 py-2">{{ $document_type->name }}</a></li>
            </ol>
        @endforeach
    </div>

    <script type="text/javascript">
        @foreach ($documents as $key => $document)
        @if (!empty($document->production))
        $(".debug_row_{{$key}}")
            .JSONView({!!$document->debug()!!}, {collapsed: true})
            .JSONView('expand', 1);
        @endif
        @endforeach

        // Manual tab system between Documents / Debug
        $('#btn-toggle-documents').on('click', function(event) {
            event.preventDefault();
            $('#documents-container').show();
            $('#debug-container').hide();
            $('#btn-toggle-documents').addClass('font-bold');
            $('#btn-toggle-debug').removeClass('font-bold');
        });

        $('#btn-toggle-debug').on('click', function(event) {
            event.preventDefault();
            $('#documents-container').hide();
            $('#debug-container').show();
            $('#btn-toggle-debug').addClass('font-bold');
            $('#btn-toggle-documents').removeClass('font-bold');
        });

        // Mail date to human readable format
        $('#mail-date-text-value').text(function(i, basicDate) {
            if (!basicDate.trim() || basicDate == '--') {
            return '--';
            }
            return moment(basicDate, 'YYYY-MM-DD').format('MMM D, YYYY');
        });

        $('#files-required-by').text(function(i, sqlDate) {
            sqlDate = sqlDate.trim();

            if (!sqlDate || sqlDate == '—') {
                var msg = sqlDate;
                $(this).css('color', '#999');
                return msg || 'No drops created';
            }
            else {
                $(this).css('color', '#c32a07');
                return moment(sqlDate).format('MMM D, YYYY - h:mma');
            }
        });

        $('#btn-add-document, #btn-close-add-document').on('click', function(event) {
            $('#add-document-menu').toggleClass('active');
        });

        $('#btn-delete-estimate').click(function(event) {
            if (!confirm('Are you absolutely sure you want to delete this estimate?')) {
                return;
            }
        });
    </script>

    @section('styles')
    @parent
    <link rel="stylesheet" href="/css/estimate-show.css" />
    <link rel="stylesheet" href="/css/jquery.jsonview.css" />
    <style type="text/css">
    #add-document-menu {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
        pointer-events: none;
        right: 30px;
        width: 220px;
    }
    #add-document-menu.active {
        opacity: 1;
        pointer-events: auto;
    }
    </style>
    @endsection

    @section('scripts')
    @parent
    <script type="text/javascript" src="/js/jquery.jsonview.min.js"></script>       
    @endsection
</x-app-layout>