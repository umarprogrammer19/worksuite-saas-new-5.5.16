<x-cards.data>

    <div id="monthly-plan">
        <div class="price-wrap border row no-gutters">
            <div class="diff-table col-6 col-md-2 col-lg-2">
                <div class="price-top">
                    <div class="price-top title">
                        <h3>@lang('superadmin.pickUp') <br> @lang('superadmin.yourPlan')</h3>
                    </div>
                    <div class="price-content">

                        <ul>
                            <li>
                                @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                            </li>
                            <li>
                                @lang('superadmin.fileStorage')
                            </li>
                            @foreach ($packageFeatures as $packageFeature)
                                @if (in_array($packageFeature, $activeModule))
                                    <li>
                                        {{ __('modules.module.' . $packageFeature) }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="all-plans col-6 col-md-10 col-lg-10">
                <div class="row no-gutters flex-nowrap flex-wrap overflow-x-auto row-scroll">
                    {{-- module list --}}
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2 package-module-list package-column">
                        <div class="price-top">
                           
                            <div class="price-content">
                                <div class="title module-list-title-sec" style="background-color: rgba(0, 0, 0, 0.075);">
                                    <h3>@lang('superadmin.pickUp') @lang('superadmin.yourPlan')</h3>
                                </div>
                                <ul>
                                   
                                    <li>
                                        @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                                    </li>
                                    <li>
                                       @lang('superadmin.fileStorage')
                                    </li>
                                    @foreach ($packageFeatures as $packageFeature)
                                        @if (in_array($packageFeature, $activeModule))
                                            <li>
                                                {{ __('modules.module.' . $packageFeature) }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                     {{-- module list end --}}
                    @foreach ($packages as $key => $item)
                        @if ($item->monthly_status == '1' || $item->default == 'lifetime')
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2 package-column">
                                <div class="pricing-table @if ($item->is_recommended == 1) price-pro @endif ">
                                    <div class="price-top">
                                        <div class="price-head text-center">
                                            <h5 class="mb-0">{{ $item->name }}</h5>
                                        </div>
                                        <div class="rate">
                                            @if ($item->default == 'no' || $item->default == '$item->default')
                                                @if (!$item->is_free)
                                                    <h2 class="mb-2">
                                                        <span
                                                            class="font-weight-bolder">{{ global_currency_format($item->monthly_price, $item->currency_id) }}</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.billedMonthly')</p>

                                                @else
                                                    <h2 class="mb-2">

                                                        <span class="font-weight-bolder">@lang('superadmin.packages.free')</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.packages.freeForever')</p>
                                                @endif
                                            @elseif ($item->default == 'lifetime')
                                                   <h2 class="mb-2">
                                                        <span
                                                            class="font-weight-bolder">{{ global_currency_format($item->price, $item->currency_id) }}</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.packages.lifeTimepackgeInfo')</p>
                                            @else
                                                <h2 class="mb-2">
                                                    <span class="font-weight-bolder">{{ $item->name }}</span>
                                                </h2>
                                                <p class="mb-0">@lang('superadmin.packages.yourDefaultPlan') <i class="fa fa-info-circle"
                                                        data-toggle="tooltip"
                                                        data-original-title="@lang('superadmin.packages.yourDefaultPlanInfo')"></i></p>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="price-content">
                                        <ul class="ui-list">
                                            <li>
                                                {{ $item->max_employees }}
                                            </li>

                                            @if ($item->max_storage_size == -1)
                                                <li>
                                                    @lang('superadmin.unlimited')
                                                </li>
                                            @else
                                                <li>
                                                    {{ $item->max_storage_size }}

                                                    @if($item->storage_unit == 'mb')
                                                        @lang('superadmin.mb')
                                                    @else
                                                        @lang('superadmin.gb')
                                                    @endif
                                                </li>
                                            @endif

                                            @php
                                                $packageModules = (array) json_decode($item->module_in_package);
                                            @endphp
                                            @foreach ($packageFeatures as $packageFeature)
                                                @if (in_array($packageFeature, $activeModule))
                                                    <li>
                                                        <i
                                                            class="bi {{ in_array($packageFeature, $packageModules) ? 'bi-check-circle text-success' : 'bi-x-circle text-danger' }}"></i>
                                                        &nbsp;
                                                    </li>
                                                @endif
                                            @endforeach

                                            @if (
                                                $item->is_free ||
                                                    $paymentActive ||
                                                    ($item->id == $company->package_id && $company->package_type == 'annual') ||
                                                    $item->default == 'yes')
                                                <li>
                                                    <x-forms.button-primary @class(['purchase-plan'])
                                                        data-package-id="{{ $item->id }}"
                                                        data-default="{{ $item->default }}"
                                                        id="purchase-plan">@lang('superadmin.packages.choosePlan')</x-forms.button-primary>
                                                </li>
                                            @else
                                                <li>
                                                    @lang('superadmin.noPaymentOptionEnable')
                                                </li>
                                            @endif
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <div id="yearly-plan" class="d-none">
        <div class="price-wrap border row no-gutters">
            <div class="diff-table col-6 col-md-2 col-lg-2">
                <div class="price-top">
                    <div class="price-top title">
                        <h3>@lang('superadmin.pickUp') <br> @lang('superadmin.yourPlan')</h3>
                        {{-- @lang('modules.frontCms.pickPlan') --}}
                    </div>
                    <div class="price-content">

                        <ul>
                            <li>
                                @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                            </li>
                            <li>
                                @lang('superadmin.fileStorage')
                            </li>
                            @foreach ($packageFeatures as $packageFeature)
                                @if (in_array($packageFeature, $activeModule))
                                    <li>
                                        {{ __('modules.module.' . $packageFeature) }}
                                    </li>
                                @endif
                            @endforeach

                        </ul>
                    </div>
                </div>
            </div>

            <div class="all-plans col-6 col-md-10 col-lg-10">
                <div class="row no-gutters flex-nowrap flex-wrap overflow-x-auto row-scroll">
                     {{-- module list --}}
                     <div class="col-6 col-sm-4 col-md-3 col-lg-2 package-module-list package-column">
                        <div class="price-top">
                           
                            <div class="price-content">
                                <div class="title module-list-title-sec" style="background-color: rgba(0, 0, 0, 0.075);">
                                    <h3>@lang('superadmin.pickUp') @lang('superadmin.yourPlan')</h3>
                                </div>
                                <ul>
                                   
                                    <li>
                                        @lang('superadmin.max') @lang('app.active') @lang('app.menu.employees')
                                    </li>
                                    <li>
                                       @lang('superadmin.fileStorage')
                                    </li>
                                    @foreach ($packageFeatures as $packageFeature)
                                        @if (in_array($packageFeature, $activeModule))
                                            <li>
                                                {{ __('modules.module.' . $packageFeature) }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                     {{-- module list end --}}
                    @foreach ($packages as $key => $item)
                        @if ($item->annual_status == '1' || $item->default == 'lifetime')
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2 package-column">
                                <div class="pricing-table @if ($item->is_recommended == 1) price-pro @endif">
                                    <div class="price-top">
                                        <div class="price-head text-center">
                                            <h5 class="mb-0">{{ $item->name }}</h5>
                                        </div>
                                        <div class="rate">

                                            @if ($item->default == 'no')
                                                @if (!$item->is_free)
                                                    <h2 class="mb-2">

                                                        <span
                                                            class="font-weight-bolder">{{ global_currency_format($item->annual_price, $item->currency_id) }}</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.billedAnnually')</p>
                                                @else
                                                    <h2 class="mb-2">

                                                        <span class="font-weight-bolder">@lang('superadmin.packages.free')</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.packages.freeForever')</p>
                                                @endif
                                            @elseif ($item->default == 'lifetime')
                                                <h2 class="mb-2">
                                                    <span class="font-weight-bolder">{{ global_currency_format($item->price, $item->currency_id) }}</span>

                                                    </h2>
                                                    <p class="mb-0">@lang('superadmin.packages.lifeTimepackgeInfo')</p>
                                            @else
                                                <h2 class="mb-2">
                                                    <span class="font-weight-bolder">{{ $item->name }}</span>
                                                </h2>
                                                <p class="mb-0">@lang('superadmin.packages.yourDefaultPlan') <i class="fa fa-info-circle"
                                                        data-toggle="tooltip"
                                                        data-original-title="@lang('superadmin.packages.yourDefaultPlanInfo')"></i></p>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="price-content">
                                        <ul>
                                            <li>
                                                {{ $item->max_employees }}
                                            </li>
                                            @if ($item->max_storage_size == -1)
                                                <li>
                                                    @lang('superadmin.unlimited')
                                                </li>
                                            @else
                                                <li>
                                                    {{ $item->max_storage_size }}

                                                    @if($item->storage_unit == 'mb')
                                                        @lang('superadmin.mb')
                                                    @else
                                                        @lang('superadmin.gb')
                                                    @endif
                                                </li>
                                            @endif
                                            @php
                                                $packageModules = (array) json_decode($item->module_in_package);
                                            @endphp
                                            @foreach ($packageFeatures as $packageFeature)
                                                @if (in_array($packageFeature, $activeModule))
                                                    <li>
                                                        <i
                                                            class="bi {{ in_array($packageFeature, $packageModules) ? 'bi-check-circle text-success' : 'bi-x-circle text-danger' }}"></i>
                                                        &nbsp;
                                                    </li>
                                                @endif
                                            @endforeach
                                            @if (
                                                $item->is_free ||
                                                    $paymentActive ||
                                                    ($item->id == $company->package_id && $company->package_type == 'annual') ||
                                                    $item->default == 'yes')
                                                <li>
                                                    <x-forms.button-primary @class(['purchase-plan'])
                                                        data-package-id="{{ $item->id }}"
                                                        data-default="{{ $item->default }}"
                                                        id="purchase-plan">@lang('superadmin.packages.choosePlan')</x-forms.button-primary>
                                                </li>
                                            @else
                                                <li>
                                                    @lang('superadmin.noPaymentOptionEnable')
                                                </li>
                                            @endif
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    <style>
        .package-module-list {
            display: none;
        }

        .module-list-title-sec {
            border-radius: 7px;
            padding: 66px 0px;
            background-color: rgba(0, 0, 0, 0.075);
            text-align: center;
        }
        /* Horizontal scrolling for screens below 800px */
        @media (max-width: 800px) {
            .price-wrap {
                margin: 0;
                border: none !important;
            }

            .diff-table {
                display: none !important; /* Hide the feature list on mobile */
            }

            .package-module-list {
                display: block !important;
            }

            .all-plans {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            .row-scroll {
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }

            .package-column {
                min-width: 280px !important;
                max-width: 280px !important;
                flex: 0 0 280px !important;
                margin-bottom: 0;
                padding: 0 10px;
            }
        }

        /* Responsive styles for plan page */
        @media (max-width: 767.98px) {
            /* Mobile-first card layout */
            .price-wrap {
                margin: 0;
                border: none !important;
            }

            .diff-table {
                display: none !important; /* Hide the feature list on mobile */
            }

            .package-module-list {
                display: block !important;
            }

            .all-plans {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
            }

            .row-scroll {
                flex-wrap: nowrap !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
                -webkit-overflow-scrolling: touch;
            }

            .package-column {
                min-width: 280px !important;
                max-width: 280px !important;
                flex: 0 0 280px !important;
                margin-bottom: 0;
                padding: 0 10px;
            }

            .pricing-table {
                border: 1px solid #e4e8ec !important;
                border-radius: 12px !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
                margin-bottom: 0 !important;
                background: white;
                overflow: hidden;
            }

            .price-top.title {
                display: none !important; /* Hide the title section on mobile */
            }

            .price-head {
                border-radius: 12px 12px 0 0 !important;
                padding: 20px 15px !important;
            }

            .price-head h5 {
                font-size: 18px !important;
                font-weight: 600 !important;
                margin: 0 !important;
            }

            .rate {
                padding: 20px 15px !important;
                background: #f8f9fa !important;
            }

            .rate h2 {
                margin: 0 0 10px 0 !important;
            }

            .rate h2 span {
                font-size: 28px !important;
                font-weight: 700 !important;
                color: #333 !important;
            }

            .rate p {
                font-size: 14px !important;
                color: #666 !important;
                margin: 0 !important;
            }

            .price-content {
                padding: 20px 15px !important;
            }

            .price-content ul {
                padding: 0 !important;
                margin: 0 !important;
            }

            .price-content li {
                font-size: 14px !important;
                padding: 12px 0 !important;
                border-bottom: 1px solid #f0f0f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
            }

            .price-content li:last-child {
                border-bottom: none !important;
                padding-top: 20px !important;
                margin-top: 10px !important;
            }

            .price-content li:before {
                content: attr(data-feature);
                font-weight: 500;
                color: #333;
            }

            .pricing-table .rate h2 span {
                font-size: 28px !important;
            }

            .pricing-table .rate p {
                font-size: 14px !important;
            }

            .purchase-plan {
                font-size: 14px !important;
                padding: 12px 24px !important;
                width: 100% !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
            }

            /* Feature labels for mobile */
            .price-content li:nth-child(1):before {
                content: "Max Employees:";
            }

            .price-content li:nth-child(2):before {
                content: "Storage:";
            }
        }

        @media (max-width: 575.98px) {
            .package-column {
                padding: 0 8px;
                margin-bottom: 0;
                min-width: 260px !important;
                max-width: 260px !important;
                flex: 0 0 260px !important;
            }

            .price-head {
                padding: 18px 12px !important;
            }

            .price-head h5 {
                font-size: 16px !important;
            }

            .rate {
                padding: 18px 12px !important;
            }

            .rate h2 span {
                font-size: 24px !important;
            }

            .rate p {
                font-size: 13px !important;
            }

            .price-content {
                padding: 18px 12px !important;
            }

            .price-content li {
                font-size: 13px !important;
                padding: 10px 0 !important;
            }

            .purchase-plan {
                font-size: 13px !important;
                padding: 10px 20px !important;
            }
        }

        @media (max-width: 480px) {
            .package-column {
                padding: 0 6px;
                margin-bottom: 0;
                min-width: 240px !important;
                max-width: 240px !important;
                flex: 0 0 240px !important;
            }

            .price-head {
                padding: 16px 10px !important;
            }

            .price-head h5 {
                font-size: 15px !important;
            }

            .rate {
                padding: 16px 10px !important;
            }

            .rate h2 span {
                font-size: 22px !important;
            }

            .rate p {
                font-size: 12px !important;
            }

            .price-content {
                padding: 16px 10px !important;
            }

            .price-content li {
                font-size: 12px !important;
                padding: 8px 0 !important;
            }

            .purchase-plan {
                font-size: 12px !important;
                padding: 8px 16px !important;
            }
        }

        /* Very small devices */
        @media (max-width: 375px) {
            .package-column {
                padding: 0 5px;
                margin-bottom: 0;
                min-width: 220px !important;
                max-width: 220px !important;
                flex: 0 0 220px !important;
            }

            .price-head {
                padding: 14px 8px !important;
            }

            .price-head h5 {
                font-size: 14px !important;
            }

            .rate {
                padding: 14px 8px !important;
            }

            .rate h2 span {
                font-size: 20px !important;
            }

            .rate p {
                font-size: 11px !important;
            }

            .price-content {
                padding: 14px 8px !important;
            }

            .price-content li {
                font-size: 11px !important;
                padding: 6px 0 !important;
            }

            .purchase-plan {
                font-size: 11px !important;
                padding: 6px 12px !important;
            }
        }

        /* Extra small devices */
        @media (max-width: 320px) {
            .package-column {
                padding: 0 4px;
                margin-bottom: 0;
                min-width: 200px !important;
                max-width: 200px !important;
                flex: 0 0 200px !important;
            }

            .price-head {
                padding: 12px 6px !important;
            }

            .price-head h5 {
                font-size: 13px !important;
            }

            .rate {
                padding: 12px 6px !important;
            }

            .rate h2 span {
                font-size: 18px !important;
            }

            .rate p {
                font-size: 10px !important;
            }

            .price-content {
                padding: 12px 6px !important;
            }

            .price-content li {
                font-size: 10px !important;
                padding: 5px 0 !important;
            }

            .purchase-plan {
                font-size: 10px !important;
                padding: 5px 10px !important;
            }
        }

        /* Tablet specific adjustments */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .package-column {
                max-width: 33.333% !important;
                flex: 0 0 33.333% !important;
            }

            .price-top.title h3 {
                font-size: 15px !important;
                padding: 25px 15px !important;
            }

            .price-content li {
                font-size: 14px;
                padding: 10px 8px;
            }

            .pricing-table .rate h2 span {
                font-size: 18px !important;
            }

            .pricing-table .rate p {
                font-size: 12px !important;
            }

            .pricing-table .price-head h5 {
                font-size: 15px !important;
                padding: 12px 8px !important;
            }

            .purchase-plan {
                font-size: 13px !important;
                padding: 8px 12px !important;
            }
        }

        /* Large screen adjustments */
        @media (min-width: 992px) {
            .package-column {
                max-width: 16.666% !important;
                flex: 0 0 16.666% !important;
            }
        }

        /* Improve horizontal scrolling on mobile */
        .row-scroll {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .row-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .row-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .row-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .row-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Ensure proper spacing and borders */
        .price-wrap {
            border-radius: 8px;
            overflow: hidden;
        }

        .pricing-table {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .price-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .price-content ul {
            flex: 1;
            margin: 0;
            list-style: none;
        }

        .price-content li:last-child {
            margin-top: auto;
            padding-top: 15px;
        }

        /* Additional mobile optimizations */
        @media (max-width: 767.98px) {
            .price-wrap {
                margin: 0 -10px;
            }

            .diff-table, .all-plans {
                padding: 0 10px;
            }
        }

        @media (max-width: 480px) {
            .price-wrap {
                margin: 0 -8px;
            }

            .diff-table, .all-plans {
                padding: 0 8px;
            }
        }

        @media (max-width: 375px) {
            .price-wrap {
                margin: 0 -6px;
            }

            .diff-table, .all-plans {
                padding: 0 6px;
            }
        }

        @media (max-width: 320px) {
            .price-wrap {
                margin: 0 -4px;
            }

            .diff-table, .all-plans {
                padding: 0 4px;
            }
        }

        /* Improve touch targets on mobile */
        @media (max-width: 767.98px) {
            .purchase-plan {
                min-height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .price-content li {
                min-height: 32px;
                display: flex;
                align-items: center;
            }
        }

        @media (max-width: 480px) {
            .purchase-plan {
                min-height: 32px;
            }

            .price-content li {
                min-height: 28px;
            }
        }

        @media (max-width: 375px) {
            .purchase-plan {
                min-height: 30px;
            }

            .price-content li {
                min-height: 26px;
            }
        }

        @media (max-width: 320px) {
            .purchase-plan {
                min-height: 28px;
            }

            .price-content li {
                min-height: 24px;
            }
        }

        /* Mobile card enhancements */
        @media (max-width: 767.98px) {
            .pricing-table.price-pro {
                border: 2px solid var(--header_color) !important;
                transform: scale(1.02);
                box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important;
            }

            .pricing-table:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 25px rgba(0,0,0,0.2) !important;
                transition: all 0.3s ease;
            }

            /* Feature icons styling */
            .price-content li i {
                font-size: 16px !important;
                margin-left: 10px !important;
            }

            .price-content li i.bi-check-circle {
                color: #28a745 !important;
            }

            .price-content li i.bi-x-circle {
                color: #dc3545 !important;
            }
        }
    </style>

</x-cards.data>
