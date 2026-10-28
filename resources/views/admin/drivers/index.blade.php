@extends('layouts.app')

@section('title', 'Drivers')

@section('page-name', 'drivers')

@section('page-title', 'Drivers')

@section('page-subtitle')
Manage driver applications and approvals
@endsection


@section('content')

<style>

.driver-search {
    margin-bottom: 18px;
}

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


.driver-details-modal {
    max-width: 900px;
    max-height: 92vh;
    overflow-y: auto;
    padding: 24px;
    position: relative;
}

.driver-modal-close {
    position: absolute;
    right: 20px;
    top: 16px;

    width: 34px;
    height: 34px;

    border: 0;
    background: transparent;

    font-size: 26px;
    line-height: 1;

    cursor: pointer;
    color: #52627a;
}

.driver-detail-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 8px;
}

.driver-detail-avatar {
    width: 48px;
    height: 48px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #d9ecff;
    color: #1167f2;

    font-weight: 700;
}

.driver-detail-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.driver-detail-name h2 {
    margin: 0;
}

.driver-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 14px;
}

.driver-detail-section {
    border: 1px solid #dce5f2;
    border-radius: 16px;
    padding: 16px;
    background: #f8fbff;
}

.driver-detail-section.full {
    grid-column: 1 / -1;
}

.driver-detail-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    color: #52698d;
    margin-bottom: 14px;
}

.driver-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.driver-info-grid.cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.driver-info-item {
    min-width: 0;
}

.driver-info-label {
    display: block;
    font-size: 13px;
    color: #71819b;
    margin-bottom: 3px;
}

.driver-info-value {
    color: #002264;
    font-size: 14px;
    font-weight: 500;

    overflow-wrap: anywhere;
}

.document-badges,
.payment-method-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.document-chip,
.payment-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;

    padding: 5px 10px;

    border-radius: 999px;

    font-size: 13px;
    font-weight: 500;
}

.document-chip.uploaded {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #9be6ad;
}

.document-chip.missing {
    background: #fee2e2;
    color: #ef4444;
    border: 1px solid #f5aaaa;
}

.payment-chip {
    background: #e7f1ff;
    color: #1167f2;
    border: 1px solid #a7cbff;
}

.driver-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}

@media (max-width: 768px) {

    .driver-detail-grid,
    .driver-info-grid,
    .driver-info-grid.cols-3 {
        grid-template-columns: 1fr;
    }

}

</style>


{{-- ================================================
    STATS
================================================ --}}

<div class="grid grid-4 mb" id="stats">

    {{-- Total --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-blue">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M5 17H3v-5l2-5h14l2 5v5h-2"
                    ></path>

                    <circle cx="7.5" cy="17" r="2"></circle>
                    <circle cx="16.5" cy="17" r="2"></circle>

                    <path d="M9.5 17h5"></path>
                </svg>

            </div>

            <div>

                <p class="stat-label">
                    Total Drivers
                </p>

                <p class="stat-value">
                    {{ $totalDrivers }}
                </p>

            </div>

        </div>

    </div>


    {{-- Pending --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-amber">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    ></circle>

                    <path
                        d="M12 7v5l3 2"
                    ></path>
                </svg>

            </div>

            <div>

                <p class="stat-label">
                    Pending
                </p>

                <p
                    class="stat-value"
                    id="pending-count"
                >
                    {{ $pendingDrivers }}
                </p>

            </div>

        </div>

    </div>


    {{-- Approved --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-green">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    ></circle>

                    <path
                        d="M8 12l3 3 5-6"
                    ></path>
                </svg>

            </div>

            <div>

                <p class="stat-label">
                    Approved
                </p>

                <p
                    class="stat-value"
                    id="approved-count"
                >
                    {{ $approvedDrivers }}
                </p>

            </div>

        </div>

    </div>


    {{-- Rejected --}}
    <div class="card reveal">

        <div class="stat">

            <div class="stat-icon tone-red">

                <svg
                    aria-hidden="true"
                    class="icon"
                    viewBox="0 0 24 24"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    ></circle>

                    <path
                        d="M15 9l-6 6M9 9l6 6"
                    ></path>
                </svg>

            </div>

            <div>

                <p class="stat-label">
                    Rejected
                </p>

                <p
                    class="stat-value"
                    id="rejected-count"
                >
                    {{ $rejectedDrivers }}
                </p>

            </div>

        </div>

    </div>

</div>


{{-- ================================================
    LISTING
================================================ --}}

<div class="card reveal">

    {{-- Tabs --}}
    <div
        class="tabs"
        id="driver-tabs"
    >

        <button
            type="button"
            class="tab
            {{ $status === 'pending' ? 'active' : '' }}"
            data-status="pending"
        >
            Pending
        </button>


        <button
            type="button"
            class="tab
            {{ $status === 'approved' ? 'active' : '' }}"
            data-status="approved"
        >
            Approved
        </button>


        <button
            type="button"
            class="tab
            {{ $status === 'rejected' ? 'active' : '' }}"
            data-status="rejected"
        >
            Rejected
        </button>


        <button
            type="button"
            class="tab
            {{ $status === 'all' ? 'active' : '' }}"
            data-status="all"
        >
            All
        </button>

    </div>


    {{-- Search --}}
    <form
        action="{{ route('admin.drivers.index') }}"
        method="GET"
        id="driver-search-form"
        class="search driver-search"
    >

        <svg
            aria-hidden="true"
            class="icon"
            viewBox="0 0 24 24"
        >
            <circle
                cx="11"
                cy="11"
                r="7"
            ></circle>

            <path
                d="M21 21l-4.3-4.3"
            ></path>
        </svg>


        <input
            type="text"
            class="input"
            id="driver-q"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search driver, phone, vehicle or plate"
            autocomplete="off"
        >

        <input
            type="hidden"
            id="driver-status"
            name="status"
            value="{{ $status }}"
        >

    </form>


    <div id="driver-list-area">

        @include(
            'admin.drivers.partials.list'
        )

    </div>

</div>


<!-- modal container -->
<div
    class="modal-backdrop"
    id="driver-details-modal"
>
    <div
        class="modal driver-details-modal"
    >

        <button
            type="button"
            class="driver-modal-close"
            id="driver-modal-close"
            aria-label="Close"
        >
            ×
        </button>

        <div id="driver-details-content">

            <div
                style="
                    padding:40px;
                    text-align:center;
                "
            >
                Loading...
            </div>

        </div>

    </div>
</div>

@endsection