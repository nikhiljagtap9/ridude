@extends('layouts.app')

@section('title', 'Trips')

@section('page-name', 'trips')

@section('page-title', 'Trips')

@section('page-subtitle')
<span id="sub">
    {{ number_format($totalTrips) }} trips in selected range
</span>
@endsection


@section('content')

<style>
    .pagination-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .ridude-pagination {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
    }

    .ridude-pagination .page-btn {
        min-width: 38px;
        height: 38px;
        padding: 0 11px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border: 1px solid #dfe3e8;
        border-radius: 8px;

        background: #fff;
        color: #1f2937;

        font-size: 14px;
        font-weight: 600;

        text-decoration: none;

        transition: 0.2s ease;
    }

    .ridude-pagination a.page-btn:hover {
        background: #f5f6f8;
        border-color: #b7bdc7;
    }

    .ridude-pagination .page-btn.active {
        background: #111827;
        border-color: #111827;
        color: #fff;
    }

    .ridude-pagination .page-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .page-dots {
        min-width: 28px;
        height: 38px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        color: #6b7280;
        font-size: 14px;
        font-weight: 600;
    }
</style>


{{-- =========================================================
    FILTERS
========================================================= --}}

<div class="card mb reveal">

    <form
        method="GET"
        action="{{ route('admin.trips.index') }}"
        id="trip-filter-form"
    >

        <div class="row">

            <div class="field">

                <label for="from">
                    From
                </label>

                <input
                    class="input"
                    id="from"
                    name="from"
                    type="date"
                    value="{{ request('from') }}"
                />

            </div>


            <div class="field">

                <label for="to">
                    To
                </label>

                <input
                    class="input"
                    id="to"
                    name="to"
                    type="date"
                    value="{{ request('to') }}"
                />

            </div>


            <div
                class="tabs"
                id="tabs"
                style="margin: 0"
            >

                <button
                    type="button"
                    aria-pressed="true"
                    class="tab active"
                    data-value="daily"
                >
                    Daily
                </button>

                <button
                    type="button"
                    aria-pressed="false"
                    class="tab"
                    data-value="weekly"
                >
                    Weekly
                </button>

                <button
                    type="button"
                    aria-pressed="false"
                    class="tab"
                    data-value="monthly"
                >
                    Monthly
                </button>

            </div>


            <button
                type="submit"
                class="btn"
            >
                Filter
            </button>


            @if(request('from') || request('to'))

                <a
                    href="{{ route('admin.trips.index') }}"
                    class="btn btn-outline"
                >
                    Clear
                </a>

            @endif

        </div>

    </form>

</div>


{{-- =========================================================
    CHART
========================================================= --}}

<div class="card mb reveal">

    <h2 class="card-title">
        Trips
    </h2>


    <div
        class="chart"
        id="chart"
    >

        <svg
            preserveAspectRatio="xMidYMid meet"
            viewBox="0 0 640 280"
        >

            {{-- Grid Line 0 --}}
            <line
                class="grid-line"
                x1="44"
                x2="630"
                y1="252"
                y2="252"
            ></line>

            <text
                text-anchor="end"
                x="36"
                y="256"
            >
                0
            </text>


            {{-- Grid Line 3 --}}
            <line
                class="grid-line"
                x1="44"
                x2="630"
                y1="191.5"
                y2="191.5"
            ></line>

            <text
                text-anchor="end"
                x="36"
                y="195.5"
            >
                3
            </text>


            {{-- Grid Line 6 --}}
            <line
                class="grid-line"
                x1="44"
                x2="630"
                y1="131"
                y2="131"
            ></line>

            <text
                text-anchor="end"
                x="36"
                y="135"
            >
                6
            </text>


            {{-- Grid Line 9 --}}
            <line
                class="grid-line"
                x1="44"
                x2="630"
                y1="70.5"
                y2="70.5"
            ></line>

            <text
                text-anchor="end"
                x="36"
                y="74.5"
            >
                9
            </text>


            {{-- Grid Line 12 --}}
            <line
                class="grid-line"
                x1="44"
                x2="630"
                y1="10"
                y2="10"
            ></line>

            <text
                text-anchor="end"
                x="36"
                y="14"
            >
                12
            </text>


            {{-- Default sample bars.
                 Your existing JS can rebuild these from #rows. --}}

            @php
                $chartRows = collect($trips->items())
                    ->groupBy(function ($trip) {
                        return \Carbon\Carbon::parse(
                            $trip->trip_date
                        )->format('d M');
                    })
                    ->map(fn ($items) => $items->count());

                $chartValues = $chartRows->values();

                $maxValue = max(
                    4,
                    $chartValues->max() ?? 1
                );

                $maxValue = ceil(
                    $maxValue / 4
                ) * 4;

                $count = max(
                    1,
                    $chartRows->count()
                );

                $barAreaWidth = 586;

                $barWidth =
                    $barAreaWidth / $count;
            @endphp


            @foreach($chartRows as $label => $value)

                @php
                    $index = $loop->index;

                    $height =
                        (242 * $value) /
                        max(1, $maxValue);

                    $x =
                        44 +
                        ($index * $barWidth) +
                        ($barWidth * 0.15);

                    $width =
                        $barWidth * 0.7;

                    $y =
                        252 - $height;
                @endphp


                <rect
                    class="bar"
                    data-tip="{{ $label }}: {{ $value }} trips"
                    x="{{ $x }}"
                    y="{{ $y }}"
                    width="{{ $width }}"
                    height="{{ $height }}"
                    rx="5"
                ></rect>


                @if(
                    $loop->first ||
                    $loop->last ||
                    $loop->index % max(1, ceil($count / 7)) === 0
                )

                    <text
                        text-anchor="middle"
                        x="{{ 44 + ($index * $barWidth) + ($barWidth / 2) }}"
                        y="272"
                    >
                        {{ $label }}
                    </text>

                @endif

            @endforeach

        </svg>

    </div>

</div>


{{-- =========================================================
    TRIP RECORDS
========================================================= --}}

<div class="card reveal">

    <h2 class="card-title">
        Trip records
    </h2>


    <div
        class="table-wrap scroll"
    >

        <table style="min-width: 860px">

            <thead>

                <tr>
                    <th>Trip ID</th>
                    <th>Date</th>
                    <th>Rider</th>
                    <th>Driver</th>
                    <th>Route</th>
                    <th>Fare</th>
                    <th>Status</th>
                </tr>

            </thead>


            <tbody id="rows">

                @forelse($trips as $trip)

                    <tr
                        data-date="{{ $trip->trip_date }}"
                    >

                        {{-- Trip ID --}}
                        <td>

                            <b>
                                TRP-{{
                                    str_pad(
                                        $trip->trip_id,
                                        5,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                }}
                            </b>

                        </td>


                        {{-- Date --}}
                        <td>

                            {{ \Carbon\Carbon::parse(
                                $trip->trip_date
                            )->format('d M Y') }}

                        </td>


                        {{-- Rider --}}
                        <td>
                            {{ $trip->rider_name }}
                        </td>


                        {{-- Driver --}}
                        <td>
                            {{ $trip->driver_name }}
                        </td>


                        {{-- Route --}}
                        <td class="small">

                            {{ $trip->start_address }}

                            →

                            {{ $trip->destination_address }}

                        </td>


                        {{-- Fare --}}
                        <td>

                            ${{ number_format(
                                (float) $trip->fare_amount,
                                2
                            ) }}

                        </td>


                        {{-- Status --}}
                        <td>

                            @if(
                                $trip->booking_status ===
                                'completed'
                            )

                                <span
                                    class="badge tone-green"
                                >
                                    Completed
                                </span>


                            @elseif(
                                $trip->booking_status ===
                                'cancelled'
                            )

                                <span
                                    class="badge tone-red"
                                >
                                    Cancelled
                                </span>


                            @elseif(
                                $trip->trip_status ===
                                'active'
                            )

                                <span
                                    class="badge tone-blue"
                                >
                                    Active
                                </span>


                            @elseif(
                                $trip->booking_status ===
                                'confirmed'
                            )

                                <span
                                    class="badge tone-amber"
                                >
                                    Confirmed
                                </span>


                            @else

                                <span class="badge">

                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $trip->booking_status
                                        )
                                    ) }}

                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                            style="
                                text-align: center;
                                padding: 30px;
                            "
                        >
                            No trips found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- =====================================================
        PAGINATION
    ====================================================== --}}

    @if($trips->hasPages())

        <div class="pagination-wrap">

            <div class="ridude-pagination">


                {{-- Previous --}}
                @if($trips->onFirstPage())

                    <span
                        class="page-btn disabled"
                        aria-disabled="true"
                    >
                        ‹
                    </span>

                @else

                    <a
                        href="{{ $trips->previousPageUrl() }}"
                        class="page-btn"
                        rel="prev"
                    >
                        ‹
                    </a>

                @endif


                @php

                    $current =
                        $trips->currentPage();

                    $last =
                        $trips->lastPage();

                    $start =
                        max(
                            1,
                            $current - 2
                        );

                    $end =
                        min(
                            $last,
                            $current + 2
                        );

                @endphp


                {{-- First page --}}
                @if($start > 1)

                    <a
                        href="{{ $trips->url(1) }}"
                        class="page-btn"
                    >
                        1
                    </a>


                    @if($start > 2)

                        <span class="page-dots">
                            ...
                        </span>

                    @endif

                @endif


                {{-- Page range --}}
                @for(
                    $page = $start;
                    $page <= $end;
                    $page++
                )

                    @if(
                        $page ==
                        $current
                    )

                        <span
                            class="page-btn active"
                            aria-current="page"
                        >
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{ $trips->url($page) }}"
                            class="page-btn"
                        >
                            {{ $page }}
                        </a>

                    @endif

                @endfor


                {{-- Last page --}}
                @if($end < $last)

                    @if(
                        $end <
                        ($last - 1)
                    )

                        <span class="page-dots">
                            ...
                        </span>

                    @endif


                    <a
                        href="{{ $trips->url($last) }}"
                        class="page-btn"
                    >
                        {{ $last }}
                    </a>

                @endif


                {{-- Next --}}
                @if(
                    $trips->hasMorePages()
                )

                    <a
                        href="{{ $trips->nextPageUrl() }}"
                        class="page-btn"
                        rel="next"
                    >
                        ›
                    </a>

                @else

                    <span
                        class="page-btn disabled"
                        aria-disabled="true"
                    >
                        ›
                    </span>

                @endif

            </div>

        </div>

    @endif

</div>


{{-- =========================================================
    SVG TEMPLATES USED BY YOUR EXISTING JS
========================================================= --}}

<template id="bar-template">

    <svg>
        <rect
            class="bar"
            rx="5"
        ></rect>
    </svg>

</template>


<template id="chart-label-template">

    <svg>
        <text
            text-anchor="middle"
        ></text>
    </svg>

</template>

@endsection