<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Estimate') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg px-6 py-4 border-t-4 border-blue-600">

                <div class="text-xl font-semibold border-b border-gray-300 mb-4 pb-2">Estimate Details</div>

                @if (session('error'))
                    <div class="text-red-600 mb-4">
                    {{ session('error') }}
                    </div>
                @endif

                <form action="{{route('estimate.store')}}" method="POST" autocomplete="off">
                {{ csrf_field() }}

                <div class="col-lg-8 col-lg-offset-2">

                    <div class="box box-primary">

                        <div class="flex mb-4 items-center{{ $errors->has('client_reference_number') ? ' has-error' : '' }}">
                            <label for="client_reference_number" class="mr-4 font-semibold w-[180px]">Job Number *</label>
                            <div class="">
                                <input id="client_reference_number" name="client_reference_number" type="text" class="w-[400px]" required value="{{ old('client_reference_number') }}">
                                @if ($errors->has('client_reference_number'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('client_reference_number') }}</strong>
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex mb-4 items-center">
                            <label for="client_reference_description" class="mr-4 font-semibold w-[180px]">Job Name *</label>
                            <div class="col-sm-9">
                                <input id="client_reference_description" name="client_reference_description" type="text" class="w-[400px]" required>
                            </div>
                        </div>

                        <div class="flex mb-4 items-center">
                            <label for="client_reference_mail_date" class="mr-4 font-semibold w-[180px]">Mail Date</label>
                            <div class="col-sm-9">
                                <input type="text" id="client_reference_mail_date" name="client_reference_mail_date" class="w-[400px]" required autocomplete="off">
                            </div>
                        </div>

                        <div class="flex mb-4 items-center">
                            <label for="cusip" class="mr-4 font-semibold w-[180px]">CUSIP</label>
                            <div class="col-sm-9">
                                <input id="cusip" name="cusip" type="text" class="w-[400px]" required />
                            </div>
                        </div>

                        @if (auth()->user()->isAdmin() && auth()->user()->hasPermissions('estimate_admin'))
                        <div class="flex mb-4 items-center">
                            <label for="company_id" class="mr-4 font-semibold w-[180px]">Company</label>
                            <div class="col-sm-9">
                                <select id="company_id" name="company_id" class="form-control" required>
                                <option></option>
                                @foreach ($all_companies as $company)
                                <option value="{{$company->id}}" {{$company->id == $user_company_id ? 'selected' : ''}}>{{$company->name}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        @if (auth()->user()->isAdmin() && auth()->user()->hasPermissions('price_management'))
                        <div class="flex mb-4 items-center">
                            <label for="company_id" class="mr-4 font-semibold w-[180px]">Pricing Rates</label>
                            <div class="col-sm-9">
                                <select name="pricing_rate_set_id" class="form-control" required>
                                <option></option>
                                @foreach ($pricing_rate_sets as $rate_set)
                                    <option value="{{$rate_set->id}}">{{$rate_set->company->name}}: {{$rate_set->name}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="form-group">
                            <div class="flex">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 mr-4">Submit</button>
                                <a class="text-blue-800 pt-2" href="{{route('estimates.index')}}">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>

    @section('scripts')
    @parent
        <script type="text/javascript" src="/js/jquery-ui.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"></script>

        <script type="text/javascript">
        (function(w) {
            w.taylor.statHolidaysDb = {!! html_entity_decode(json_encode($stat_holidays_db)) !!};
            w.taylor.pricingRateSets = {!! html_entity_decode(json_encode($pricing_rate_sets)) !!};
            
            $(document).ready(function() {
                $('#company_id').on('change', function(event) {
                    var selectedCompanyId = +$(event.currentTarget).val();
                    var companyActiveRateSet = _.find(taylor.pricingRateSets, { is_active: 1, company_id: selectedCompanyId });
                        $('select[name="pricing_rate_set_id"]').val(_.get(companyActiveRateSet, 'id'));
                    });

                var blockedDates = getStatHolidayDates();
                $('#client_reference_mail_date').datepicker({
                    dateFormat: 'yy-mm-dd',
                    beforeShowDay: function(date) {
                        var stringDate = $.datepicker.formatDate('yy-mm-dd', date);
                        if ($.inArray(stringDate, blockedDates) !== -1) {
                            return [false, "blocked-date-css", "This date is unavailable"]; 
                        }
                        return [true, ''];
                    }
                });
            });

            function getStatHolidayDates() {
                // from static content ShippingService::getStatHolidays
                return _.map(w.taylor.statHolidaysDb, function(customDateObj) {
                    return moment(customDateObj['date_string'], 'D-MMM-YYYY').format('YYYY-MM-DD');
                });
            }
        }(this));
        </script>
    @endsection

    @section('styles')
    @parent
    <link rel="stylsheet" href="/css/jquery-ui.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.structure.min.css" />
    <link rel="stylesheet" href="/css/jquery-ui.theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css" integrity="sha512-LT9fy1J8pE4Cy6ijbg96UkExgOjCqcxAC7xsnv+mLJxSvftGVmmc236jlPTZXPcBRQcVOWoK1IJhb1dAjtb4lQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    @endsection
</x-app-layout>