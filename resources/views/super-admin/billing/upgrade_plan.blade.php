@extends('layouts.app')

@push('styles')
    <style>
        .package-value {
        background-color: rgba(0, 0, 0, 0.075);
        text-align: center;
        }

        .price-tabs a {
        border: 1px solid #222;
        color: #222;
        font-weight: 500;
        font-size: 20px;
        padding: 10px 50px;
        }

        .price-tabs a:hover {
        color: #222;
        }

        .price-tabs a.active {
        background-color: var(--main-color);
        color: #fff;
        }
        .pricing-section .border{
        border: 1px solid #e4e8ec !important;
        }
        .pricing-table {
        text-align: center;
        border-right: 1px solid #dee2e6 !important
        }

        .pricing-table.border {
        border-right: 0 !important;
        }

        .pricing-table .rate {
        padding: 14px 0;
        background-color: rgba(0, 0, 0, 0.075);
        }
        .pricing-table .rate sup {
        top: 13px;
        left: 5px;
        font-size: 0.35em;
        font-weight: 500;
        vertical-align: top;
        }

        .pricing-table .rate sub {
        font-size: 0.30em;
        color: #969696;
        left: -7px;
        bottom: 0;
        }

        .pricing-table .price-head {
        background-color: var(--header_color);
        color: white;
        padding: 15px;
        }
        .pricing-table .price-head h5{
        font-size: 18px !important;
        }
        .pricing-table.price-pro .price-head {
        background-color:var(--header_color);
        }
        .pricing-table.price-pro .price-head h5{
        color:#fff;
        }
        .diff-table{
        border-right: 1px solid #e4e8ec;
        }

        .pricing-table.price-pro {
        -webkit-box-shadow: 0 1px 30px 1px rgba(0, 0, 0, 0.1) !important;
                box-shadow: 0 1px 30px 1px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid var(--header_color) !important;
        border-top: 0;
        border-bottom: 0;
        }

        .overflow-x-auto {
        overflow-x: auto;
        }

        .price-content li {
        padding: 10px;
        }

        .price-content li:nth-child(even) {
        background-color:rgba(0, 0, 0, 0.075);
        }

        @media (min-width: 992px) {
            .price-content li {
                padding: 10px 20px;
            }

            .pricing-table .rate h2 span{
                font-size: 30px;
            }

            .price-top.title h3 {
                padding: 44px 30px 46px;
                margin-bottom: 0;
                background-color: rgba(0, 0, 0, 0.075);
            }
        }

        .price-content .blue {
        color:#457de4;
        }

        .price-content .zmdi-close-circle {
        color: #ff0000;
        }

        @media (max-width: 1199.98px) {
            .price-wrap {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .pricing-table .rate h2 span{
                font-size: 15px;
            }
            .price-top.title h3 {
                padding: 47px 17px;
                margin-bottom: 0;
                background-color: rgba(0, 0, 0, 0.075);
                font-size: 15px;
            }
        }

        .sticky {
            position: sticky;
            bottom: 0;
            background-color: white;
        }

        .package-column {
            max-width: 25%;
            flex: 0 0 25%
        }

        .rate p {
            font-size: 12px;
        }

        /* Enhanced Responsive Styles */
        @media (max-width: 767.98px) {
            /* Mobile-first layout */
            .content-wrapper {
                padding: 15px 10px;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
                margin-bottom: 20px;
            }

            .btn-group .btn {
                margin-bottom: 10px;
                width: 100%;
                padding: 15px 20px;
                font-size: 16px;
                border-radius: 8px;
                font-weight: 600;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 20px;
                align-items: center;
            }

            .col-2 {
                width: 100%;
                max-width: 250px;
                margin: 0 auto;
            }

            .price-tabs a {
                font-size: 16px;
                padding: 8px 20px;
            }

            .pricing-table .price-head h5 {
                font-size: 16px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 18px;
            }

            .price-top.title h3 {
                padding: 30px 15px;
                font-size: 14px;
            }

            .price-content li {
                padding: 8px;
                font-size: 14px;
            }
        }

        @media (max-width: 575.98px) {
            .content-wrapper {
                padding: 12px 8px;
            }

            .btn-group .btn {
                font-size: 15px;
                padding: 12px 16px;
                margin-bottom: 8px;
            }

            .d-flex.justify-content-between {
                gap: 16px;
            }

            .col-2 {
                max-width: 220px;
            }

            .price-tabs a {
                font-size: 14px;
                padding: 6px 15px;
            }

            .pricing-table .price-head h5 {
                font-size: 14px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 16px;
            }

            .price-top.title h3 {
                padding: 25px 10px;
                font-size: 13px;
            }

            .price-content li {
                padding: 6px;
                font-size: 13px;
            }

            .rate p {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .content-wrapper {
                padding: 10px 6px;
            }

            .btn-group .btn {
                font-size: 14px;
                padding: 10px 14px;
                margin-bottom: 6px;
            }

            .d-flex.justify-content-between {
                gap: 14px;
            }

            .col-2 {
                max-width: 200px;
            }

            .price-tabs a {
                font-size: 13px;
                padding: 5px 12px;
            }

            .pricing-table .price-head h5 {
                font-size: 13px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 15px;
            }

            .price-top.title h3 {
                padding: 20px 8px;
                font-size: 12px;
            }

            .price-content li {
                padding: 5px;
                font-size: 12px;
            }
        }

        /* Very small devices */
        @media (max-width: 375px) {
            .content-wrapper {
                padding: 8px 4px;
            }

            .btn-group .btn {
                font-size: 13px;
                padding: 8px 12px;
                margin-bottom: 6px;
            }

            .d-flex.justify-content-between {
                gap: 12px;
            }

            .col-2 {
                max-width: 180px;
            }

            .price-tabs a {
                font-size: 12px;
                padding: 4px 10px;
            }

            .pricing-table .price-head h5 {
                font-size: 12px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 14px;
            }

            .price-top.title h3 {
                padding: 18px 6px;
                font-size: 11px;
            }

            .price-content li {
                padding: 4px;
                font-size: 11px;
            }

            .rate p {
                font-size: 10px;
            }
        }

        /* Extra small devices */
        @media (max-width: 320px) {
            .content-wrapper {
                padding: 6px 3px;
            }

            .btn-group .btn {
                font-size: 12px;
                padding: 6px 10px;
                margin-bottom: 5px;
            }

            .d-flex.justify-content-between {
                gap: 10px;
            }

            .col-2 {
                max-width: 160px;
            }

            .price-tabs a {
                font-size: 11px;
                padding: 3px 8px;
            }

            .pricing-table .price-head h5 {
                font-size: 11px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 13px;
            }

            .price-top.title h3 {
                padding: 15px 5px;
                font-size: 10px;
            }

            .price-content li {
                padding: 3px;
                font-size: 10px;
            }

            .rate p {
                font-size: 9px;
            }
        }

        /* Tablet specific adjustments */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .btn-group .btn {
                padding: 10px 30px;
                font-size: 18px;
            }

            .price-tabs a {
                font-size: 18px;
                padding: 8px 30px;
            }

            .pricing-table .price-head h5 {
                font-size: 16px !important;
            }

            .pricing-table .rate h2 span {
                font-size: 20px;
            }

            .price-top.title h3 {
                padding: 35px 20px;
                font-size: 16px;
            }

            .price-content li {
                padding: 10px 15px;
                font-size: 15px;
            }
        }

        /* Ensure proper spacing on all devices */
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mt-1 {
            margin-top: 0.25rem !important;
        }

        .col-12 {
            padding: 0 15px;
        }

        /* Improve button responsiveness */
        .btn {
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        /* Improve form control responsiveness */
        .form-control {
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--main-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        /* Additional mobile optimizations */
        @media (max-width: 767.98px) {
            .row {
                margin-left: -10px;
                margin-right: -10px;
            }

            .col-12 {
                padding: 0 10px;
            }

            .mb-2 {
                margin-bottom: 0.75rem !important;
            }

            /* Mobile button enhancements */
            .btn-group .btn {
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border: none;
            }

            .btn-group .btn:hover {
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }

            .btn-group .btn.btn-active {
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }

            /* Currency selector enhancement */
            .form-control {
                border-radius: 8px;
                border: 2px solid #e9ecef;
                padding: 12px 16px;
                font-size: 14px;
            }

            .form-control:focus {
                border-color: var(--main-color);
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
            }
        }

        @media (max-width: 480px) {
            .row {
                margin-left: -8px;
                margin-right: -8px;
            }

            .col-12 {
                padding: 0 8px;
            }

            .mb-2 {
                margin-bottom: 1rem !important;
            }
        }

        @media (max-width: 375px) {
            .row {
                margin-left: -6px;
                margin-right: -6px;
            }

            .col-12 {
                padding: 0 6px;
            }
        }

        @media (max-width: 320px) {
            .row {
                margin-left: -4px;
                margin-right: -4px;
            }

            .col-12 {
                padding: 0 4px;
            }
        }

        /* Mobile-first improvements */
        @media (max-width: 767.98px) {
            /* Better mobile spacing */
            .content-wrapper > .row {
                margin-top: 0;
            }

            /* Enhanced mobile alerts */
            .alert {
                border-radius: 8px;
                margin-bottom: 20px;
                padding: 15px;
                border: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            /* Mobile form improvements */
            .select-picker {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                background-position: right 12px center;
                background-repeat: no-repeat;
                background-size: 16px;
                padding-right: 40px;
            }
        }
    </style>
@endpush

@section('content')

<div class="content-wrapper">

    <div class="row">

        <div class="col-12 mb-2 mt-1 text-center">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                <?php Session::forget('success');?>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                <?php Session::forget('error');?>
            @endif

            <div class="d-flex justify-content-between">
                <div class="btn-group" role="group" aria-label="Basic example">
                    <button type="button" class="btn btn-secondary f-16 btn-active monthly package-type" data-package-type="monthly">@lang('app.monthly')</button>

                    <button type="button" class="btn btn-secondary f-16 annually package-type" data-package-type="annual">@lang('app.annually')</button>
                </div>
                <div class="col-2">
                    <select id="currency"  class="form-control select-picker" data-size="8">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}" @selected($currency->id == global_setting()->currency_id)>
                                {{ $currency->currency_name }} ({{ $currency->currency_symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="col-sm-12" id="price-plan">
            @include('super-admin.billing.plan')
        </div>
    </div>

</div>

@endsection

@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const list = document.querySelector('.ui-list');
        const items = list.querySelectorAll('li');
        const lastItem = items[items.length - 1];

        lastItem.classList.add('sticky');
        $('body').on('click', '.monthly', function() {
            $('.annually').removeClass('btn-active');
            $('#monthly-plan').removeClass('d-none');
            $('#yearly-plan').addClass('d-none');
            $(this).addClass('btn-active');
             deactivateCurrentPackageButton();
        });

        $('body').on('click', '.annually', function() {
            $('.monthly').removeClass('btn-active');
            $('#yearly-plan').removeClass('d-none');
            $('#monthly-plan').addClass('d-none');
            $(this).addClass('btn-active');
             deactivateCurrentPackageButton();
        });

        $('body').on('click', '.purchase-plan', function() {
            var packageId = $(this).data('package-id');
            var packageType = $('.package-type.btn-active').data('package-type');

            var url = "{{ route('billing.select-package',':id') }}?type=" + packageType;
            url = url.replace(':id', packageId);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $(document).ready(function() {
            deactivateCurrentPackageButton();
        });
        function deactivateCurrentPackageButton()
        {
            var packageType = $('.package-type.btn-active').data('package-type');
            var companyPackageId = '{{company()->package_id}}';

            $('.purchase-plan').each(function() {
                if(($(this).data('default') == 'yes' || $(this).data('default') == 'lifetime')&& $(this).data('package-id') == companyPackageId){
                    $(this).attr('disabled', true);
                    $(this).html('@lang('superadmin.packages.currentPlan')');
                }
                else if($(this).data('package-id') == companyPackageId && packageType == '{{company()->package_type}}'){
                    $(this).attr('disabled', true);
                    $(this).html('@lang('superadmin.packages.currentPlan')');
                }
                else{
                    $(this).attr('disabled', false);
                    $(this).html('@lang('superadmin.packages.choosePlan')');
                }
            });

        }

        // #currency on change request and load price plan on that currency
        $('body').on('change', '#currency', function () {
            let currencyId = $(this).val();
            let url = '{{ route('billing.upgrade_plan') }}';
            $.easyAjax({
                url: url,
                type: "GET",
                data: {
                    'currencyId':currencyId
                },
                success: function (response) {
                    $('#price-plan').html(response.view);
                    $('.monthly').trigger('click');
                }
            })

        });
    </script>
@endpush
