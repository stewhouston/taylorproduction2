<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    {{ __('Shipping: Estimate') . ' #' . $estimate->id }} ({{ $estimate->company->name }})
                </h2>
                <div class="text-lg">
                    <span><a href="{{ route('estimate.show', $estimate->id) }}">{{ $estimate->client_reference_number }}</a> {{ $estimate->client_reference_description }}</span>
                </div>
                <div class="text-lg">
                    <span class="font-semibold">Mail Date:</span> <span>{{ $estimate->client_reference_mail_date }}</span>
                </div>
            </div>
            <div class="flex items-center">
                <button id="btn-save-all-drops"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 mr-2"
                >Save All</button>

                <a class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150 cursor-pointer no-underline"
                    href="{{ route('estimate.show', $estimate->id) }}"
                >
                    Back to Estimate
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <form class="lg:px-8 xl:w-full xl:mx-4 2xl:max-w-[90%] 2xl:mx-auto">
            @csrf
            <div class="flex bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">
                <div class="min-w-[540px] pr-8">
                    <h3 class="text-2xl font-semibold border-b border-gray-200 mb-2">Locations</h3>
                    <table id="ship-address-list" cellpadding="8" class="w-full">
                        <thead>
                            <th class="bg-gray-800 text-white">&nbsp;</th>
                            <th class="bg-gray-800 text-white text-left">Company</th>
                            <th class="bg-gray-800 text-white text-left">City</th>
                            <th class="bg-gray-800 text-white">&nbsp;</th>
                        </thead>
                        <tbody>
                        <!-- render target -->
                        </tbody>
                    </table>
                </div>
                <div class="w-full">
                    <div id="app-notifications"></div>
                    <h3 class="text-2xl font-semibold border-b border-gray-200 mb-2">Drops</h3>
                    <table id="drops-app-table" class="shipping-drops-table w-full" cellpadding="8">
                        <thead>
                            <th class="bg-gray-800 text-white">&nbsp;</th>
                            <th class="bg-gray-800 text-white">&nbsp;</th>
                            <th class="bg-gray-800 text-white text-left">Company</th>
                            <th class="bg-gray-800 text-white text-left">City/Addr</th>
                            <th class="bg-gray-800 text-white text-left">Delivery Date</th>
                            <th class="bg-gray-800 text-white"># Documents</th>
                            <th class="bg-gray-800 text-white">Cost</th>
                            <!-- qty no longer  being displayed -->
                            <th class="bg-gray-800 text-white" style="text-align: center; display: none;">Qty</th>
                            <th class="bg-gray-800 text-white">&nbsp;</th>
                        </thead>
                        <tbody id="drops-tbody">
                            <tr id="empty-drops-block-row">
                                <td colspan="8">
                                    <div class="empty-drops-block-message" style="background-color: #eee; text-align: center; padding: 180px 0; font-size: 18px; color: #777;">
                                        Add a drop from the column to the left
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
    (function(w) {
        w.taylor = w.taylor = {};
        var sessionFz = Object.freeze;
        w.taylor.estimateId = {{ $estimate->id }};
        w.taylor.estimate = sessionFz({!! html_entity_decode(json_encode($estimate)) !!});
        w.taylor.shiptos = sessionFz({!! html_entity_decode(json_encode($shiptos)) !!});
        w.taylor.zones = sessionFz({!! html_entity_decode(json_encode($zones)) !!});
        w.taylor.documents = sessionFz({!! html_entity_decode(json_encode($documents)) !!});
        w.taylor.dropInstances = {!! html_entity_decode(json_encode($drop_instances)) !!};
        w.taylor.statHolidaysDb = {!! html_entity_decode(json_encode($stat_holidays_db)) !!};
        w.taylor.initNotification = {!! html_entity_decode(json_encode($init_notification)) !!};
    }(window));
    </script>
    <script type="text/javascript" src="/js/custom-address.js"></script>
    <script type="text/javascript" src="/js/shipping-app.js"></script>

    @section('styles')
    @parent
    <link rel="stylsheet" href="/css/jquery-ui.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.structure.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css" />
    <style type="text/css">
    .ship-list-indx {
        display: block;
        color: #aaa;
        font-size: 16px;
        text-align: center;
        padding-left: 10px;
    }
    .btn-reassign-shipto {
        position: absolute;
        top: 12px;
        right: 16px;
    }
    </style>
    @endsection

    @section('scripts')
    @parent
    <script type="text/javascript" src="/js/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>
    @endsection
</x-app-layout>