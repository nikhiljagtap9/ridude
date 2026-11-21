@extends('layouts.app')

@section('title', 'Driver Subscriptions')

@section('page-name', 'subscriptions')

@section('page-title', 'Driver Subscriptions')

@section('page-subtitle')
Weekly and monthly subscription payments
@endsection


@section('content')

    <div class="grid grid-side">

        {{-- =====================================================
            DRIVER SUMMARY
        ====================================================== --}}

        <div>

            <h2
                style="
                    font-size:16px;
                    margin:0 0 12px;
                "
            >
                By driver
            </h2>


            <div id="drivers">

                @forelse($drivers as $driver)

                    <button
                        type="button"
                        class="card driver-card"
                        data-id="{{ $driver->id }}"
                        data-name="{{ $driver->full_name }}"
                    >

                        <b>
                            {{ $driver->full_name }}
                        </b>

                        <br>

                        <span class="small muted">

                            {{ $driver->payments_count }}

                            {{
                                $driver->payments_count == 1
                                    ? 'subscription'
                                    : 'subscriptions'
                            }}

                        </span>


                        <p class="amount">

                            @php
                                $driverCurrency =
                                    $driver->currency
                                    ?? 'USD';
                            @endphp

                            {{ $driverCurrency }}

                            {{ number_format(
                                $driver->total_amount ?? 0,
                                2
                            ) }}

                        </p>

                    </button>

                @empty

                    <div class="card">

                        <p class="muted">
                            No driver subscriptions found.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>



        {{-- =====================================================
            SUBSCRIPTION TABLE
        ====================================================== --}}

        <div class="card reveal is-visible">

            {{-- =================================================
                TABS + DRIVER FILTER
            ================================================== --}}

            <div
                class="tabs"
                id="tabs"
                style="
                    display:flex;
                    align-items:center;
                    gap:10px;
                    flex-wrap:wrap;
                "
            >

                <button
                    type="button"
                    aria-pressed="true"
                    class="tab active"
                    data-value="all"
                >
                    All
                </button>


                <button
                    type="button"
                    aria-pressed="false"
                    class="tab"
                    data-value="week"
                >
                    Weekly
                </button>


                <button
                    type="button"
                    aria-pressed="false"
                    class="tab"
                    data-value="month"
                >
                    Monthly
                </button>


                <select
                    id="driver-filter"
                    class="input"
                    style="
                        width:auto;
                        min-width:220px;
                        margin-left:auto;
                    "
                >
                    <option value="">
                        All Drivers
                    </option>

                    @foreach($drivers as $driver)

                        <option
                            value="{{ $driver->id }}"
                        >
                            {{ $driver->full_name }}
                        </option>

                    @endforeach
                </select>

                <a
                    href="{{ route('admin.drivers-subscriptions.export') }}"
                    class="btn btn-outline"
                    id="subscription-export-btn"
                >
                    Export CSV
                </a>

            </div>



            {{-- =================================================
                TABLE
            ================================================== --}}

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Driver</th>

                            <th>Plan</th>

                            <th>Amount</th>

                            <th>Start</th>

                            <th>End</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody id="rows">

                        @forelse(
                            $subscriptions
                            as $subscription
                        )

                            @php

                                $status =
                                    strtolower(
                                        $subscription->status
                                        ?? ''
                                    );

                                $billingCycle =
                                    strtolower(
                                        $subscription->billing_cycle
                                        ?? ''
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Start Date
                                |--------------------------------------------------------------------------
                                */

                                $startDate =
                                    $subscription
                                        ->subscription_start_at
                                    ?? $subscription
                                        ->trial_start_at;


                                /*
                                |--------------------------------------------------------------------------
                                | End Date
                                |--------------------------------------------------------------------------
                                */

                                $endDate =
                                    $subscription
                                        ->subscription_end_at
                                    ?? $subscription
                                        ->trial_end_at;

                            @endphp


                            <tr
                                data-driver="{{
                                    $subscription
                                        ->driver_name
                                }}"
                                data-driver-id="{{ $subscription->user_id }}"
                                data-plan="{{
                                    $billingCycle
                                }}"
                            >

                                {{-- ==========================================
                                    SUBSCRIPTION ID
                                =========================================== --}}

                                <td>

                                    <b>

                                        SUB-{{
                                            str_pad(
                                                $subscription->id,
                                                4,
                                                '0',
                                                STR_PAD_LEFT
                                            )
                                        }}

                                    </b>

                                </td>



                                {{-- ==========================================
                                    DRIVER
                                =========================================== --}}

                                <td>

                                    {{
                                        $subscription
                                            ->driver_name
                                    }}

                                </td>



                                {{-- ==========================================
                                    PLAN
                                =========================================== --}}

                                <td>

                                    {{
                                        $subscription
                                            ->plan_name
                                        ?? ucfirst(
                                            $billingCycle
                                        )
                                    }}

                                </td>



                                {{-- ==========================================
                                    AMOUNT
                                =========================================== --}}

                                <td>

                                    {{
                                        $subscription
                                            ->currency
                                        ?? 'USD'
                                    }}

                                    {{
                                        number_format(
                                            $subscription
                                                ->price
                                                ?? 0,
                                            2
                                        )
                                    }}

                                </td>



                                {{-- ==========================================
                                    START DATE
                                =========================================== --}}

                                <td>

                                    @if($startDate)

                                        {{
                                            \Carbon\Carbon::parse(
                                                $startDate
                                            )
                                            ->format(
                                                'd M Y'
                                            )
                                        }}

                                    @else

                                        -

                                    @endif

                                </td>



                                {{-- ==========================================
                                    END DATE
                                =========================================== --}}

                                <td>

                                    @if($endDate)

                                        {{
                                            \Carbon\Carbon::parse(
                                                $endDate
                                            )
                                            ->format(
                                                'd M Y'
                                            )
                                        }}

                                    @else

                                        -

                                    @endif

                                </td>



                                {{-- ==========================================
                                    STATUS
                                =========================================== --}}

                                <td>

                                    @if(
                                        $status === 'active'
                                    )

                                        <span
                                            class="badge tone-green"
                                        >
                                            Active
                                        </span>


                                    @elseif(
                                        $status === 'trial'
                                    )

                                        <span
                                            class="badge tone-blue"
                                        >
                                            Trial
                                        </span>


                                    @elseif(
                                        $status === 'expired'
                                    )

                                        <span
                                            class="badge tone-red"
                                        >
                                            Expired
                                        </span>


                                    @elseif(
                                        $status === 'cancelled'
                                    )

                                        <span
                                            class="badge tone-red"
                                        >
                                            Cancelled
                                        </span>


                                    @elseif(
                                        $status === 'failed'
                                    )

                                        <span
                                            class="badge tone-red"
                                        >
                                            Failed
                                        </span>


                                    @else

                                        <span class="badge">

                                            {{
                                                ucfirst(
                                                    $subscription
                                                        ->status
                                                    ?? 'Unknown'
                                                )
                                            }}

                                        </span>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr class="empty-row">

                                <td
                                    colspan="7"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                    "
                                >

                                    <span class="muted">
                                        No subscriptions found.
                                    </span>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <template id="empty-template">

        <tr class="empty-row">

            <td
                colspan="7"
                style="
                    text-align:center;
                    padding:30px;
                "
            >

                <span class="muted">
                    No subscriptions found.
                </span>

            </td>

        </tr>

    </template>

@endsection