@extends('layouts.app')

@section('title', 'Fare Calculations')

@section('page-name', 'fare-calculations')

@section('page-title', 'Fare Calculations')

@section('page-subtitle')
Manage the fixed pickup amount and mileage cost for each distance level.
@endsection


@section('content')


<style>

    .fare-grid {
        display: grid;

        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );

        gap: 18px;

        margin-bottom: 26px;
    }


    .fare-card {
        position: relative;
    }


    .fare-card-head {
        display: flex;

        align-items:
            flex-start;

        justify-content:
            space-between;

        margin-bottom: 16px;
    }


    .fare-card h2 {
        margin: 0 0 4px;

        font-size: 20px;
    }


    .fare-range {
        margin: 0;

        color: #6b7280;

        font-size: 14px;
    }


    .fare-edit {
        width: 36px;
        height: 36px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        border:
            1px solid #dce4ef;

        border-radius: 9px;

        background: #fff;

        cursor: pointer;

        color: #334155;
    }


    .fare-edit:hover {
        background: #f5f8fc;
    }


    .fare-values {
        display: grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        gap: 12px;

        margin-bottom: 14px;
    }


    .fare-value-box {
        padding: 15px;

        border-radius: 14px;
    }


    .fare-value-box.base {
        background:
            #e4f2ff;
    }


    .fare-value-box.per-mile {
        background:
            #e5f9e8;
    }


    .fare-value-label {
        display: block;

        font-size: 13px;

        color: #64748b;

        margin-bottom: 3px;
    }


    .fare-value {
        font-size: 22px;

        font-weight: 700;
    }


    .base-value {
        color: #1266d4;
    }


    .mile-value {
        color: #22a447;
    }


    .fare-example {
        margin: 0;

        font-size: 13px;

        color: #6b7280;
    }



    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */


    .fare-preview-row {
        display: flex;

        align-items: flex-end;

        gap: 16px;

        flex-wrap: wrap;
    }


    .fare-preview-price {
        min-width: 150px;

        padding: 12px 20px;

        border-radius: 16px;

        background:
            linear-gradient(
                135deg,
                #1987dc,
                #12b89a
            );

        color: #fff;
    }


    .fare-preview-price span {
        display: block;

        font-size: 13px;
    }


    .fare-preview-price strong {
        display: block;

        font-size: 28px;
    }


    .fare-preview-level {
        margin-top: 5px;

        font-size: 12px;

        opacity: .9;
    }



    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */


    .fare-modal {
        max-width: 470px;
    }


    .fare-range-display {
        padding: 13px 15px;

        border:
            1px solid #dfe5ec;

        border-radius: 9px;

        background:
            #f7f9fc;

        color: #334155;

        font-weight: 600;
    }


    .fare-modal-grid {
        display: grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );

        gap: 14px;
    }


    .fare-modal-grid .full {
        grid-column:
            1 / -1;
    }


    @media(max-width:900px) {

        .fare-grid {
            grid-template-columns:
                1fr;
        }

    }


    @media(max-width:600px) {

        .fare-modal-grid {
            grid-template-columns:
                1fr;
        }


        .fare-modal-grid .full {
            grid-column:
                auto;
        }

    }

</style>



{{-- =========================================================
    FIXED 3 FARE LEVELS
========================================================= --}}

<div
    class="fare-grid"
    id="fare-levels"
>

    @forelse(
        $fareLevels
        as $level
    )

        @php

            if (
                (int) $level->level_no === 1
            ) {

                $rangeLabel =
                    '0.10 – 5.00 miles';

                $exampleMiles =
                    5.00;

            } elseif (
                (int) $level->level_no === 2
            ) {

                $rangeLabel =
                    '5.01 – 10.00 miles';

                $exampleMiles =
                    7.80;

            } else {

                $rangeLabel =
                    'More than 10 miles';

                $exampleMiles =
                    12.00;
            }


            $exampleFare =
                (float) $level->base_price
                +
                (
                    $exampleMiles
                    *
                    (float)
                    $level->price_per_mile
                );

        @endphp


        <div
            class="
                card
                fare-card
                reveal
                is-visible
            "

            data-id="{{
                $level->id
            }}"

            data-level="{{
                $level->level_no
            }}"

            data-min="{{
                $level->min_miles
            }}"

            data-max="{{
                $level->max_miles
            }}"

            data-base="{{
                $level->base_price
            }}"

            data-rate="{{
                $level->price_per_mile
            }}"
        >


            <div class="fare-card-head">

                <div>

                    <h2>
                        {{ $level->level_name }}
                    </h2>


                    <p class="fare-range">

                        {{ $rangeLabel }}

                    </p>

                </div>



                {{-- Edit --}}

                <button
                    type="button"

                    class="fare-edit"

                    data-edit

                    data-id="{{
                        $level->id
                    }}"

                    data-level="{{
                        $level->level_no
                    }}"

                    data-name="{{
                        $level->level_name
                    }}"

                    data-range="{{
                        $rangeLabel
                    }}"

                    data-base="{{
                        $level->base_price
                    }}"

                    data-rate="{{
                        $level->price_per_mile
                    }}"

                    aria-label="
                        Edit
                        {{ $level->level_name }}
                    "
                >

                    <svg
                        aria-hidden="true"

                        class="icon"

                        viewBox="0 0 24 24"
                    >

                        <path
                            d="M12 20h9"
                        ></path>


                        <path
                            d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"
                        ></path>

                    </svg>

                </button>

            </div>



            {{-- Pricing --}}

            <div class="fare-values">


                <div
                    class="
                        fare-value-box
                        base
                    "
                >

                    <span
                        class="
                            fare-value-label
                        "
                    >
                        Fixed pickup
                    </span>


                    <span
                        class="
                            fare-value
                            base-value
                        "
                    >

                        ${{
                            number_format(
                                $level->base_price,
                                2
                            )
                        }}

                    </span>

                </div>



                <div
                    class="
                        fare-value-box
                        per-mile
                    "
                >

                    <span
                        class="
                            fare-value-label
                        "
                    >
                        Per mile
                    </span>


                    <span
                        class="
                            fare-value
                            mile-value
                        "
                    >

                        ${{
                            number_format(
                                $level->price_per_mile,
                                2
                            )
                        }}

                    </span>

                </div>

            </div>



            {{-- Example --}}

            <p class="fare-example">

                Example:

                {{ number_format(
                    $exampleMiles,
                    2
                ) }}

                miles =

                ${{
                    number_format(
                        $exampleFare,
                        2
                    )
                }}

            </p>

        </div>


    @empty

        <div class="card">

            <p class="muted">

                Fare levels have not
                been configured.

            </p>

        </div>

    @endforelse

</div>



{{-- =========================================================
    FARE PREVIEW
========================================================= --}}

<div class="card reveal">

    <h2 class="card-title">
        Fare Preview
    </h2>


    <p
        class="muted"
        style="margin-top:-8px;"
    >
        Enter a rider's travelling distance
        to preview the calculated fare.
    </p>


    <div class="fare-preview-row">


        <div class="field">

            <label for="fare-distance">

                Rider distance
                (miles)

            </label>


            <input
                type="number"

                min="0.10"

                step="0.01"

                class="input"

                id="fare-distance"

                value="5.50"
            >

        </div>



        <div class="fare-preview-price">

            <span>
                Rider pays
            </span>


            <strong
                id="fare-preview-value"
            >
                $0.00
            </strong>


            <div
                class="fare-preview-level"

                id="fare-preview-level"
            ></div>

        </div>

    </div>

</div>



{{-- =========================================================
    EDIT MODAL
========================================================= --}}

<div
    class="modal-backdrop"

    id="fare-level-modal"
>

    <div
        class="
            modal
            fare-modal
        "
    >


        <h2 id="fare-modal-title">

            Edit Fare Level

        </h2>


        <input
            type="hidden"

            id="fare-level-id"
        >



        <div class="fare-modal-grid">


            {{-- Level --}}

            <div class="field">

                <label>
                    Fare Level
                </label>


                <div
                    class="
                        fare-range-display
                    "

                    id="fare-level-name-display"
                >
                    -
                </div>

            </div>



            {{-- Range --}}

            <div class="field">

                <label>
                    Distance Range
                </label>


                <div
                    class="
                        fare-range-display
                    "

                    id="fare-range-display"
                >
                    -
                </div>

            </div>



            {{-- Fixed Amount --}}

            <div
                class="
                    field
                    full
                "
            >

                <label
                    for="fare-base-price"
                >

                    Fixed Pickup Amount
                    (USD)

                </label>


                <input
                    type="number"

                    class="input"

                    id="fare-base-price"

                    min="0"

                    step="0.01"

                    placeholder="0.75"
                >

            </div>



            {{-- Per Mile --}}

            <div
                class="
                    field
                    full
                "
            >

                <label
                    for="fare-price-mile"
                >

                    Price Per Mile
                    (USD)

                </label>


                <input
                    type="number"

                    class="input"

                    id="fare-price-mile"

                    min="0"

                    step="0.01"

                    placeholder="1.00"
                >

            </div>

        </div>



        <div class="modal-actions">


            <button
                type="button"

                class="
                    btn
                    btn-outline
                "

                id="fare-modal-cancel"
            >

                Cancel

            </button>



            <button
                type="button"

                class="btn"

                id="fare-modal-save"
            >

                Save Changes

            </button>

        </div>

    </div>

</div>



{{-- =========================================================
    JS DATA
========================================================= --}}

@php

    $fareLevelsJson =
        $fareLevels
        ->map(
            function ($level) {

                return [
                    'id' =>
                        (int)
                        $level->id,

                    'level_no' =>
                        (int)
                        $level->level_no,

                    'level_name' =>
                        $level->level_name,

                    'min_miles' =>
                        (float)
                        $level->min_miles,

                    'max_miles' =>
                        $level->max_miles !== null
                            ? (float)
                                $level->max_miles
                            : null,

                    'base_price' =>
                        (float)
                        $level->base_price,

                    'price_per_mile' =>
                        (float)
                        $level->price_per_mile,
                ];

            }
        )
        ->values();

@endphp


<script>

    window.fareLevels =
        @json($fareLevelsJson);

</script>


@endsection