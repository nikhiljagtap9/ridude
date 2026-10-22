@extends('layouts.app')

@section('title', 'Trip Transactions')

@section('page-name', 'trip-transactions')

@section('page-title', 'Trip Transactions')

@section('page-subtitle')
Fare payments from completed trips
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
    opacity: .4;
    cursor: not-allowed;
}

.page-dots {
    min-width: 28px;
    height: 38px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    color: #6b7280;
}

.transaction-filter {
    margin-bottom: 20px;
}

.method-text {
    max-width: 280px;
    white-space: normal;
    line-height: 1.5;
}

</style>


{{-- ========================================================
    STATS
======================================================== --}}

<div class="grid grid-3 mb">

    {{-- Revenue --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-green">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M6 3h12M6 8h12M6 13l8.5 8M6 13h3a5 5 0 0 0 0-10"
                    ></path>
                </svg>

            </div>


            <div>

                <p class="stat-label">
                    Total Revenue
                </p>

                <p class="stat-value">

                    ${{
                        number_format(
                            $totalRevenue ?? 0,
                            2
                        )
                    }}

                </p>

            </div>

        </div>

    </div>


    {{-- Transactions --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-blue">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M4 2v20l3-2 3 2 2-2 2 2 3-2 3 2V2l-3 2-3-2-2 2-2-2-3 2z"
                    ></path>

                    <path
                        d="M8 8h8M8 12h8M8 16h5"
                    ></path>
                </svg>

            </div>


            <div>

                <p class="stat-label">
                    Transactions
                </p>

                <p class="stat-value">
                    {{
                        number_format(
                            $totalTransactions ?? 0
                        )
                    }}
                </p>

            </div>

        </div>

    </div>


    {{-- Average --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-amber">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M22 7l-8.5 8.5-5-5L2 17"
                    ></path>

                    <path
                        d="M16 7h6v6"
                    ></path>
                </svg>

            </div>


            <div>

                <p class="stat-label">
                    Average Fare
                </p>

                <p class="stat-value">

                    ${{
                        number_format(
                            $averageFare ?? 0,
                            2
                        )
                    }}

                </p>

            </div>

        </div>

    </div>

</div>


{{-- ========================================================
    FILTER
======================================================== --}}

<div class="card mb reveal">

    <form
        method="GET"
        action="{{ route('admin.trip-transactions.index') }}"
        class="transaction-filter"
    >

        <div class="row">

            <div class="field">

                <label for="from">
                    From
                </label>

                <input
                    class="input"
                    type="date"
                    id="from"
                    name="from"
                    value="{{ request('from') }}"
                >

            </div>


            <div class="field">

                <label for="to">
                    To
                </label>

                <input
                    class="input"
                    type="date"
                    id="to"
                    name="to"
                    value="{{ request('to') }}"
                >

            </div>


            <div
                class="field"
                style="flex:1;"
            >

                <label for="q">
                    Search
                </label>

                <input
                    class="input"
                    type="text"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Trip ID, rider or driver"
                    autocomplete="off"
                >

            </div>


            <div
                class="field"
                style="
                    justify-content:flex-end;
                "
            >

                <label>&nbsp;</label>

                <button
                    type="submit"
                    class="btn"
                >
                    Filter
                </button>

            </div>


            @if(
                request('from') ||
                request('to') ||
                request('q')
            )

                <div class="field">

                    <label>&nbsp;</label>

                    <a
                        href="{{ route('admin.trip-transactions.index') }}"
                        class="btn btn-outline"
                    >
                        Clear
                    </a>

                </div>

            @endif

        </div>

    </form>

</div>


{{-- ========================================================
    TRANSACTION TABLE
======================================================== --}}

<div class="card reveal">

    <h2 class="card-title">
        Transactions
    </h2>


    <div class="table-wrap scroll">

        <table style="min-width:900px;">

            <thead>

                <tr>
                    <th>Trip ID</th>
                    <th>Date</th>
                    <th>Rider</th>
                    <th>Driver</th>
                    <th>Method</th>
                    <th>Fare</th>
                    <th>Status</th>
                </tr>

            </thead>


            <tbody>

                @forelse(
                    $transactions
                    as $transaction
                )

                    <tr>

                        {{-- Trip --}}
                        <td>

                            <b>
                                TRP-{{
                                    str_pad(
                                        $transaction->trip_id,
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
                                $transaction->trip_date
                            )->format('d M Y') }}

                        </td>


                        {{-- Rider --}}
                        <td>
                            {{
                                $transaction
                                    ->rider_name
                            }}
                        </td>


                        {{-- Driver --}}
                        <td>
                            {{
                                $transaction
                                    ->driver_name
                            }}
                        </td>


                        {{-- Methods --}}
                        <td class="method-text">

                            @if(
                                !empty(
                                    $transaction
                                        ->payment_methods
                                )
                            )

                                {{
                                    $transaction
                                        ->payment_methods
                                }}

                            @else

                                <span class="muted">
                                    -
                                </span>

                            @endif

                        </td>


                        {{-- Fare --}}
                        <td>

                            ${{
                                number_format(
                                    (float)
                                    $transaction
                                        ->fare_amount,
                                    2
                                )
                            }}

                        </td>


                        {{-- Status --}}
                        <td>

                            @if(
                                $transaction
                                    ->booking_status ===
                                'completed'
                            )

                                <span
                                    class="badge tone-green"
                                >
                                    Paid
                                </span>

                            @elseif(
                                $transaction
                                    ->booking_status ===
                                'cancelled'
                            )

                                <span
                                    class="badge tone-red"
                                >
                                    Cancelled
                                </span>

                            @else

                                <span
                                    class="badge tone-amber"
                                >

                                    {{
                                        ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $transaction
                                                    ->booking_status
                                            )
                                        )
                                    }}

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
                                text-align:center;
                                padding:30px;
                            "
                        >
                            No transactions found.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- ====================================================
        PAGINATION
    ===================================================== --}}

    @if($transactions->hasPages())

        <div class="pagination-wrap">

            <div class="ridude-pagination">


                {{-- Previous --}}
                @if(
                    $transactions
                        ->onFirstPage()
                )

                    <span
                        class="page-btn disabled"
                    >
                        ‹
                    </span>

                @else

                    <a
                        href="{{
                            $transactions
                                ->previousPageUrl()
                        }}"
                        class="page-btn"
                    >
                        ‹
                    </a>

                @endif


                @php

                    $current =
                        $transactions
                            ->currentPage();

                    $last =
                        $transactions
                            ->lastPage();

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
                        href="{{
                            $transactions
                                ->url(1)
                        }}"
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


                {{-- Pages --}}
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
                        >
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{
                                $transactions
                                    ->url($page)
                            }}"
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
                        href="{{
                            $transactions
                                ->url($last)
                        }}"
                        class="page-btn"
                    >
                        {{ $last }}
                    </a>

                @endif


                {{-- Next --}}
                @if(
                    $transactions
                        ->hasMorePages()
                )

                    <a
                        href="{{
                            $transactions
                                ->nextPageUrl()
                        }}"
                        class="page-btn"
                    >
                        ›
                    </a>

                @else

                    <span
                        class="page-btn disabled"
                    >
                        ›
                    </span>

                @endif

            </div>

        </div>

    @endif

</div>

@endsection