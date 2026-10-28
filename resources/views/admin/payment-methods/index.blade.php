@extends('layouts.app')

@section('title', 'Payment Methods')

@section('page-name', 'payments')

@section('page-title', 'Payment Methods')

@section('page-subtitle')
Choose how riders and drivers can pay
@endsection


@section('content')


<style>

    .payment-method-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }


    .payment-method-actions .btn {
        flex: 1;
    }


    /* ==========================================
        PAGINATION
    ========================================== */

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

        transition: all .2s ease;
    }


    .ridude-pagination a.page-btn:hover {
        background: #f5f6f8;
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
        padding: 0 5px;

        color: #6b7280;
    }


    /* ==========================================
        DELETE BUTTON
    ========================================== */

    .payment-method-delete {
        color: #dc2626;
    }


    .payment-method-delete:hover {
        background: #fff1f2;
        border-color: #fecaca;
        color: #dc2626;
    }

</style>



{{-- =========================================================
    ADD PAYMENT METHOD
========================================================= --}}

<div class="card mb reveal">

    <form
        action="{{ route('admin.payment-methods.store') }}"
        method="POST"
        class="row"
        id="payment-method-form"
        style="flex-wrap:nowrap;"
    >

        @csrf


        <input
            class="input"
            id="mname"
            name="name"
            placeholder="New payment method name"
            autocomplete="off"
        >


        <button
            class="btn"
            type="submit"
            id="add-payment-method-btn"
        >

            <svg
                aria-hidden="true"
                class="icon"
                viewBox="0 0 24 24"
            >

                <path
                    d="M12 5v14M5 12h14"
                ></path>

            </svg>


            Add method

        </button>

    </form>

</div>



{{-- =========================================================
    PAYMENT METHOD LIST
========================================================= --}}

<div
    class="grid grid-4"
    id="payment-method-list"
>

    @forelse(
        $paymentMethods
        as $method
    )

        <div
            class="card method reveal is-visible"
            data-id="{{ $method->id }}"
        >


            {{-- =============================================
                HEADER
            ============================================== --}}

            <div class="method-top">

                <div class="stat-icon tone-blue">

                    <svg
                        aria-hidden="true"
                        class="icon"
                        viewBox="0 0 24 24"
                    >

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="14"
                            rx="2"
                        ></rect>


                        <path
                            d="M3 10h18"
                        ></path>

                    </svg>

                </div>



                {{-- Active Toggle --}}

                <button
                    type="button"

                    aria-checked="{{
                        $method->is_active
                            ? 'true'
                            : 'false'
                    }}"

                    aria-label="Toggle {{ $method->name }}"

                    class="
                        switch
                        payment-toggle
                        {{
                            $method->is_active
                                ? 'on'
                                : ''
                        }}
                    "

                    data-id="{{ $method->id }}"

                    role="switch"
                ></button>

            </div>



            {{-- =============================================
                NAME
            ============================================== --}}

            <h2
                class="payment-method-name"
            >

                {{ $method->name }}

            </h2>



            {{-- =============================================
                SLUG
            ============================================== --}}

            <p
                class="
                    muted
                    small
                    payment-method-slug
                "

                style="
                    font-size:14px;
                    margin:0 0 12px;
                "
            >

                {{ $method->slug }}

            </p>



            {{-- =============================================
                STATUS
            ============================================== --}}

            @if($method->is_active)

                <span
                    class="
                        badge
                        tone-green
                        status-badge
                    "
                >

                    Active

                </span>

            @else

                <span
                    class="
                        badge
                        tone-red
                        status-badge
                    "
                >

                    Inactive

                </span>

            @endif



            {{-- =============================================
                ACTIONS
            ============================================== --}}

            <div
                class="payment-method-actions"
            >


                {{-- Edit --}}

                <button
                    type="button"

                    class="
                        btn
                        btn-outline
                        payment-method-edit
                    "

                    data-id="{{
                        $method->id
                    }}"

                    data-name="{{
                        $method->name
                    }}"
                >

                    Edit

                </button>



                {{-- Delete --}}

                <button
                    type="button"

                    class="
                        btn
                        btn-outline
                        payment-method-delete
                    "

                    data-id="{{
                        $method->id
                    }}"
                >

                    Delete

                </button>

            </div>

        </div>


    @empty

        <div class="card">

            <p class="muted">
                No payment methods found.
            </p>

        </div>

    @endforelse

</div>



{{-- =========================================================
    PAGINATION
========================================================= --}}

@if($paymentMethods->hasPages())

    <div class="pagination-wrap">

        <div class="ridude-pagination">


            {{-- Previous --}}

            @if(
                $paymentMethods
                    ->onFirstPage()
            )

                <span
                    class="
                        page-btn
                        disabled
                    "
                >

                    ‹

                </span>

            @else

                <a
                    href="{{
                        $paymentMethods
                            ->previousPageUrl()
                    }}"

                    class="page-btn"
                >

                    ‹

                </a>

            @endif



            @php

                $current =
                    $paymentMethods
                        ->currentPage();

                $last =
                    $paymentMethods
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
                        $paymentMethods
                            ->url(1)
                    }}"

                    class="page-btn"
                >

                    1

                </a>


                @if($start > 2)

                    <span
                        class="page-dots"
                    >

                        ...

                    </span>

                @endif

            @endif



            {{-- Page numbers --}}

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
                        class="
                            page-btn
                            active
                        "
                    >

                        {{ $page }}

                    </span>

                @else

                    <a
                        href="{{
                            $paymentMethods
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

                    <span
                        class="page-dots"
                    >

                        ...

                    </span>

                @endif



                <a
                    href="{{
                        $paymentMethods
                            ->url($last)
                    }}"

                    class="page-btn"
                >

                    {{ $last }}

                </a>

            @endif



            {{-- Next --}}

            @if(
                $paymentMethods
                    ->hasMorePages()
            )

                <a
                    href="{{
                        $paymentMethods
                            ->nextPageUrl()
                    }}"

                    class="page-btn"
                >

                    ›

                </a>

            @else

                <span
                    class="
                        page-btn
                        disabled
                    "
                >

                    ›

                </span>

            @endif

        </div>

    </div>

@endif



{{-- =========================================================
    EDIT PAYMENT METHOD MODAL
========================================================= --}}

<div
    class="modal-backdrop"
    id="payment-edit-modal"
>

    <div class="modal">


        <h2>
            Edit Payment Method
        </h2>


        <input
            type="hidden"
            id="edit-payment-id"
        >



        <div class="field">

            <label
                for="edit-payment-name"
            >
                Payment Method Name
            </label>


            <input
                type="text"

                class="input"

                id="edit-payment-name"

                autocomplete="off"
            >

        </div>



        <div class="modal-actions">


            <button
                type="button"

                class="
                    btn
                    btn-outline
                "

                id="edit-payment-cancel"
            >

                Cancel

            </button>



            <button
                type="button"

                class="btn"

                id="edit-payment-save"
            >

                Update

            </button>

        </div>

    </div>

</div>


@endsection