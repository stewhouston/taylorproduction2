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
                <button id="btn-toggle-as-print-order"
                    @if ($estimate->is_print_order)
                    class="bg-orange-600 hover:bg-orange-500 active:bg-gray-300 text-white font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                    @else
                    class="bg-gray-100 hover:bg-gray-200 active:bg-gray-900 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                    @endif
                >{{ $estimate->is_print_order ? 'Unmark as Print Order' : 'Mark as Print Order' }}</button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="lg:px-8 xl:w-full xl:mx-4 2xl:max-w-[80%] 2xl:mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">

                <div id="archived-estimate-message" class="text-center bg-yellow-100 border border-yellow-800 py-2 rounded-md mb-2" style="display: {{ $estimate->is_archived ? 'block' : 'none' }};">
                    This estimate is archived. <strong>Status:</strong> <span id="archived-estimate-status">{{ ucfirst($estimate->status) }}</span>
                </div>

                @if ($estimate->estimate_comment)
                    <div class="bg-blue-100 border border-blue-800 p-2 mb-2 rounded-md" style="font-style: italic;"><strong>Comment:</strong> {{ $estimate->estimate_comment }}</div>
                @endif

                <div id="estimate-alerts-message" class="bg-red-100 border border-red-800 py-2 px-4 rounded-md mb-2" style="display: {{ $estimate->has_alert && $estimate->alerts ? 'block' : 'none' }};">
                    <strong>Alerts ({{ count($estimate->getAlerts()) }}):</strong>
                    @foreach ($estimate->getAlerts() as $alert_msg)
                        {{ $alert_msg }}<br />
                    @endforeach
                </div>

                @if (!auth()->user()->isReadOnly())
                    <section class="content-header">
                        <ul id="taylor-button-nav-bar" class="nav-horizontal text-center {{ $estimate->is_print_order ? 'nav-as-print-order' : '' }} {{ $estimate->has_alert_disable_interact ? 'nav-has-alert-disable-interact' : '' }}">
                        <li style="float: left; pointer-events: all; opacity: 1; filter: saturate(1)">
                            <a href="{{route('estimates.index')}}{{ $estimate->is_archived ? '?archives=1' : '' }}">
                            <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </li>

                        <li id="list-toggle-rush-fee" class="{{ $estimate->has_rush_fee ? 'active' : '' }}">
                            <a href="#" id="toggle-rush-fee"><i class="fa fa-bolt"></i> Rush Fee</a>
                        </li>
                        <li id="list-edit-estimate">
                            <a href="{{route('estimate.edit', $estimate->id)}}"><i class="fa fa-edit"></i> Edit Estimate</a>
                        </li>
                        <li id="list-delete-estimate">
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
                        <li id="list-edit-shipping">
                            <a href="{{route('shipping.drops', $estimate->id)}}" id="shipping-button">
                            <i class="fa fa-truck"></i> Shipping
                            </a>
                        </li>
                        <li id="list-add-document">
                            <a href="#"  id="btn-add-document">
                            <i class="fa fa-book"></i> Add Document
                            </a>
                        </li>
                        </ul>
                    </section>
                @endif

                <div class="border-t border-solid border-gray-300 mt-4 pt-4">
                    <div class="h-print-order {{ $estimate->is_print_order ? 'text-orange-600 text-3xl font-bold text-center' : 'hidden' }}">
                        <span>Print Order</span>
                    </div>
                    <div class="flex justify-between">
                        <div>
                            <div><span class="font-semibold">Number:</span> <span>{{$estimate->client_reference_number}}</span></div>
                            <div><span class="font-semibold">Description:</span> <span>{{$estimate->client_reference_description}}</span></div>
                            <div><span class="font-semibold">Mail Date:</span> <span id="mail-date-text-value">{{$estimate->client_reference_mail_date}}</span></div>
                            <div><span class="font-semibold">Files Required By:</span> <span id="files-required-by">
                                @if ($estimate->dropInstances->count() && empty($estimate->files_required_by))
                                    —
                                @elseif ($estimate->files_require_adjustment)
                                    {{ $estimate->files_require_adjustment }}
                                @else
                                    {{ $estimate->files_required_by }}
                                @endif
                            </span></div>
                            <div>
                            @if ($estimate->file_require_adjust_reason)
                                <div style="color: #666; font-style: italic; margin-bottom: 4px; font-weight: bold;">
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
                            <div><span class="font-semibold">Created By:</span> 
                            @if ($estimate->createdBy)
                                {{ $estimate->createdBy->name }}
                            @endif
                            </div>
                            <div><span class="font-semibold">Estimate Last Modified By:</span> {{ $estimate->getLastModifiedBy() }}</div>
                            <div><span class="font-semibold">Shipping Last Modified By:</span> todo</div>
                            @if ($estimate->adjustment_by)
                            <div><span class="font-semibold">Adjustments Last Modified By:</span> {{ $estimate->getAdjustLastModifiedBy() }}</div>
                            @endif
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
                        <table class="w-full mt-4 cursor-default" cellpadding="4" cellspacing="4">
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
                                        <div>&bull; Weight: {{ $document->production->weight ?? '0' }}lbs |
                                        @if ($document->production && $document->production->weight)
                                        {{ round($document->production->weight * 453.592, 0) }}g
                                        @endif</div>
                                    @if (request()->user()->hasPermissions('estimate_admin') && !empty($document->production))
                                    <div>
                                        Production Timing: {{ $document->production->hour_range}} hours
                                        {!! $document->is_digital_only ? ' | <span class="estimate-tag-digital-only text-purple-700 italic">Digital Only</span>' : '' !!}
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
                                <a class="cost-text underline cursor-pointer">
                                    {{number_format($document->production->sell, 2)}}
                                </a>
                                <div class="cost-details hidden text-green-600 leading-tight">
                                    Bind: {{number_format($document->production->bind_sell, 2)}}<br/>
                                    Print: {{number_format($document->production->sell - $document->production->bind_sell, 2)}}
                                    </div>
                            @else
                                &ndash;
                            @endif
                            </td>
                            <td class="print-action-column">
                            @if (!auth()->user()->isReadOnly() && !$estimate->has_alert_disable_interact)
                            <form method="POST" action="{{ route('estimate.document.delete', $estimate->id) }}" role="role" class="form-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="document_id" value="{{ $document->id }}" />
                                <span class="user-action-controls" style="display: inline-block;">
                                @if($document->is_custom && auth()->user()->isAdmin() && auth()->user()->hasPermissions('price_management'))
                                    <a class="btn btn-link btn-set-custom-parameters" data-document-id="{{$document->id}}" style="font-weight: bold; padding: 0;">Estimate Detail</a> |
                                @endif
                                <a type="button" class="btn-edit-estimate p-0 underline text-blue-800" href="#">
                                Edit</a>
                                | <button type="submit" class="btn-delete-document underline text-blue-800 p-0">Delete</button>
                                </span>
                                <span class="print-order-notifier" style="display: none; color: #999; font-style: italic;">Print Order</span>
                                @if (request()->user()->hasPermissions('estimate_admin'))
                                | <a class="btn-production-details text-blue-800 p-0 underline cursor-pointer"
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

                                    <a class="btn-production-details all text-blue-800 underline cursor-pointer"
                                        style="padding:0;"
                                        data-document-id="all"
                                        data-estimate-id="{{$estimate->id}}"
                                    >Production Details (All)</a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">&nbsp;</td>
                                <td>
                                    <button class="btn-adjustments-handle p-0 text-blue-800 underline"
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

                <h2 class="text-2xl my-4 cursor-default">Estimate Totals</h2>

                <table class="w-full cursor-default" cellpadding="8" cellspacing="8">
                <tbody>
                    <tr class="hover:bg-gray-100">
                        <td style="width:50%" class="font-semibold">Print Sub Total</td>
                        <td id="cell-subtotal-value">${{ number_format($costs['print_sub_total'], 2) }}</td>
                    </tr>
                @if ($estimate->print_sell_adjustment)
                <tr id="price-adjust-row" class="hover:bg-gray-100">
                    <td>
                        <div style="float: right;">Adjustment</div>
                        <div style="padding-left: 60px; color: #666; font-style: italic; float: right; cursor: default; margin-right: 6px;"
                            id="price-adjust-reason"
                        >
                            {{ $estimate->print_sell_adjust_reason }}
                        </div>
                    </td>
                    <td style="color: green;">
                        <span id="price-adjust-value">${{ number_format($estimate->print_sell_adjustment, 2) }}</span>
                    </td>
                </tr>
                @endif
                <tr id="rush-fee-quote-row" style="display: {{ $estimate->has_rush_fee ? 'table-row' : 'none' }}"  class="hover:bg-gray-100">
                    <td style="text-align: right;" class="pr-2 text-yellow-800">Rush Fee</td>
                    <td>${{ number_format($curve_fields['Delivery_Rush_Fee_Dollars'], 2) }}</td>
                </tr>
                <tr class="hover:bg-gray-100">
                    <td style="width:50%" class="font-semibold">Print Total</td>
                    <td id="cell-print-total-value"
                        style="vertical-align: top;"
                    >${{ number_format($costs['sub_total_with_rush_fee'], 2) }}</td>
                </tr>
                <tr class="hover:bg-gray-100">
                    <td class="font-semibold">Shipping Sub Total</td>
                    <td>
                    <span id="shipping-cost-value">
                        ${{ number_format($costs['ship_sub_total'], 2) }}
                    </span>

                        (<a class="btn-toggle-drop-instances underline" style="cursor: pointer;">{{ $drop_instances->count() }} drops</a>)
                    </td>
                </tr>
                <!-- toggle default hidden -->
                <tr id="drop-instances-row" style="display: none;" class="hover:bg-gray-100">
                    <td class="drops-content-cell" colspan="2" style="font-size: 14px;">
                        <!-- populated by jq -->
                    </td>
                </tr>
                @if ($estimate->drop_sell_adjustment)
                <tr id="shipping-adjust-row" class="hover:bg-gray-100">
                    <td>
                        <div style="float: right;">Adjustment</div>
                        <div style="padding-left: 60px; color: #666; font-style: italic; float: right; cursor: default; margin-right: 6px;"
                            id="shipping-adjust-reason"
                        >
                           {{ $estimate->drop_sell_adjust_reason }}
                        </div>
                    </td>
                    <td style="color: green;">
                        <span id="shipping-adjust-value">${{ number_format($estimate->drop_sell_adjustment, 2) }}</span>
                    </td>
                </tr>
                @endif
                <tr class="hover:bg-gray-100">
                    <td class="font-semibold">Shipping Total</td>
                    <td style="vertical-align: top;"
                    >
                        ${{ number_format($costs['shipping_cost'], 2) }}
                    </td>
                </tr>

                <tr class="hover:bg-gray-100">
                    <td class="font-semibold">Estimate Sub Total</td>
                    <td id="cell-estimate-subtotal-value">
                        ${{ number_format($costs['sub_total_with_ship'], 2) }}
                    </td>
                </tr>
                <tr class="hover:bg-gray-100">
                    <td class="font-semibold">HST ({{ $curve_fields['Tax_HST_Percent'] }}%)</td>
                    <td id="cell-tax-value">
                        ${{ number_format($costs['taxes'], 2) }}
                    </td>
                </tr>
                <tr class="hover:bg-gray-100">
                    <td class="font-semibold">Total</td>
                    <td id="cell-price-total">
                        ${{ number_format($costs['total'], 2) }}
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div id="add-document-menu" 
        class="bg-gray-800 text-white absolute top-0 mt-8 rounded-md"
    >
        <a class="absolute p-2 cursor-pointer" style="right: 10px; top: 6px;" id="btn-close-add-document">x</a>
        <h3 class="font-semibold text-lg px-4 py-4"><i class="fa fa-file-text-o"></i>&nbsp;&nbsp;Add Document</h3>
        <ol class="mb-4">
        @foreach ($document_types as $document_type)
            <li><a class="block px-4 py-2 text-white border-t border-gray-700" href="/estimate/{{ $estimate->id }}/document/create/{{ $document_type->slug }}">{{ $document_type->name }}</a></li>
        @endforeach
        </ol>
    </div>

    <script type="text/javascript" src="/js/estimate-archive-lightbox.js"></script>
    <script type="text/javascript">
        var w = window;

        w.estimate = {};
        // Current url without a trailing slash. For use in ajax requests.
        w.estimate.url = location.href.replace(/[\/#]$/, '');
        w.estimate.subTotal = {{ $costs['sub_total'] }};
        w.estimate.taxes = {{ $costs['taxes'] }};
        w.estimate.total = {{ $costs['total'] }};
        w.estimate.hasRushFee = {{ $estimate->has_rush_fee ? 'true' : 'false' }};
        w.estimate.isArchived = {{ $estimate->is_archived ? 'true' : 'false' }};
        w.estimate.isPrintOrder = {{ $estimate->is_print_order ? 'true' : 'false' }};
        w.estimate.adjustCount = {{ $estimate->adjust_count ?? 0 }};

        w.taylor.estimateId = {{ $estimate->id }};
        w.taylor.estimate = {!! html_entity_decode(json_encode($estimate->attributesToArray())) !!};
        w.taylor.shiptos = {!! html_entity_decode(json_encode($shiptos)) !!};
        w.taylor.documents = {!! html_entity_decode(json_encode($documents, JSON_PRETTY_PRINT)) !!};
        w.taylor.dropInstances = {!! html_entity_decode(json_encode($drop_instances)) !!};
        w.taylor.estimateLastModified = new Date("{{ $estimate->getLastModifiedAt() }}");
        w.taylor.estimateActiveRateSet = {!! html_entity_decode(json_encode($est_active_rate_set)) !!};
        w.taylor.usesInactiveRateSet = {{ $uses_inactive_rate_set ? 'true' : 'false' }};
        w.taylor.statHolidaysDb = {!! html_entity_decode(json_encode($stat_holidays_db)) !!};

        @foreach ($documents as $key => $document)
        @if (!empty($document->production))
        $(".debug_row_{{$key}}")
            .JSONView({!!$document->debug()!!}, {collapsed: true})
            .JSONView('expand', 1);
        @endif
        @endforeach
    </script>
    <script type="text/javascript" src="/js/estimate-show.js"></script>
    <script type="text/javascript" src="/js/estimate-adjust-lightbox.js"></script>

    @section('styles')
    @parent
    <link rel="stylesheet" href="/css/estimate-show.css" />
    <link rel="stylesheet" href="/css/jquery.jsonview.css" />
    <link rel="stylsheet" href="/css/jquery-ui.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.structure.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css" integrity="sha512-LT9fy1J8pE4Cy6ijbg96UkExgOjCqcxAC7xsnv+mLJxSvftGVmmc236jlPTZXPcBRQcVOWoK1IJhb1dAjtb4lQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    @endsection

    @section('scripts')
    @parent
    <script type="text/javascript" src="/js/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
    <script type="text/javascript" src="/js/jquery.jsonview.min.js"></script>       
    @endsection
</x-app-layout>