@extends('layouts.app')

@section('title', 'Payment Methods')

@section('page-name', 'payments')

@section('page-title', 'Payment Methods')

@section('page-subtitle')
Choose how riders and drivers can pay
@endsection


@section('content')

<div class="card mb reveal">

    <form
        action="{{ route('admin.payment-methods.store') }}"
        method="POST"
        class="row"
        id="payment-method-form"
        style="flex-wrap: nowrap"
    >

        @csrf

        <input
            class="input"
            id="mname"
            name="name"
            placeholder="New payment method name"
            autocomplete="off"
        />

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


<div
    class="grid grid-4"
    id="payment-method-list"
>

    @forelse($paymentMethods as $method)

        <div
            class="card method reveal is-visible"
            data-id="{{ $method->id }}"
        >

            <div class="method-top">

                <div class="stat-icon tone-blue">

                    <svg
                        aria-hidden="true"
                        class="icon"
                        viewBox="0 0 24 24"
                    >
                        <rect
                            height="14"
                            rx="2"
                            width="20"
                            x="2"
                            y="5"
                        ></rect>

                        <path
                            d="M2 10h20"
                        ></path>
                    </svg>

                </div>


                <button
                    type="button"
                    aria-checked="{{
                        $method->is_active
                            ? 'true'
                            : 'false'
                    }}"
                    aria-label="Toggle {{ $method->name }}"
                    class="switch payment-toggle
                    {{ $method->is_active ? 'on' : '' }}"
                    data-id="{{ $method->id }}"
                    role="switch"
                ></button>

            </div>


            <h2>
                {{ $method->name }}
            </h2>


            <p
                class="muted small"
                style="
                    font-size: 14px;
                    margin: 0 0 12px;
                "
            >
                {{ $method->slug }}
            </p>


            @if($method->is_active)

                <span class="badge tone-green status-badge">
                    Active
                </span>

            @else

                <span class="badge tone-red status-badge">
                    Inactive
                </span>

            @endif

        </div>

    @empty

        <div class="card">
            <p class="muted">
                No payment methods found.
            </p>
        </div>

    @endforelse

</div>

@endsection