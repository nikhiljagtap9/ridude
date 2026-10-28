@extends('layouts.app')

@section('title', 'Riders')

@section('page-name', 'riders')

@section('page-title', 'Riders')

@section('page-subtitle')
{{ number_format($totalRiders) }} registered riders
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
    color: #fff;
    border-color: #111827;
}

.ridude-pagination .page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}


.rider-details-modal {
    max-width: 650px;
    max-height: 92vh;
    overflow-y: auto;
    padding: 24px;
    position: relative;
}

.rider-modal-close {
    position: absolute;
    right: 20px;
    top: 14px;

    width: 34px;
    height: 34px;

    border: 0;
    background: transparent;

    font-size: 26px;
    color: #52627a;

    cursor: pointer;
}

.rider-detail-header {
    display: flex;
    align-items: center;
    gap: 14px;

    margin-bottom: 8px;
}

.rider-detail-avatar {
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

.rider-detail-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rider-detail-name h2 {
    margin: 0;
}

.rider-detail-grid {
    display: grid;
    gap: 14px;
    margin-top: 14px;
}

.rider-detail-section {
    border: 1px solid #dce5f2;
    border-radius: 16px;

    padding: 16px;

    background: #f8fbff;
}

.rider-detail-title {
    font-size: 13px;
    font-weight: 700;

    text-transform: uppercase;

    color: #52698d;

    margin-bottom: 14px;
}

.rider-info-grid {
    display: grid;
    grid-template-columns: repeat(
        2,
        minmax(0, 1fr)
    );

    gap: 15px;
}

.rider-info-grid.cols-3 {
    grid-template-columns: repeat(
        3,
        minmax(0, 1fr)
    );
}

.rider-info-item {
    min-width: 0;
}

.rider-info-label {
    display: block;

    font-size: 13px;

    color: #71819b;

    margin-bottom: 3px;
}

.rider-info-value {
    color: #002264;

    font-size: 14px;
    font-weight: 500;

    overflow-wrap: anywhere;
}

.rider-document-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.rider-document-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;

    padding: 5px 10px;

    border-radius: 999px;

    font-size: 13px;
    font-weight: 500;
}

.rider-document-chip.uploaded {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #9be6ad;
}

.rider-document-chip.missing {
    background: #fee2e2;
    color: #ef4444;
    border: 1px solid #f5aaaa;
}

@media (max-width: 768px) {

    .rider-info-grid,
    .rider-info-grid.cols-3 {
        grid-template-columns: 1fr;
    }

}
</style>    

<div class="card reveal">

    {{-- Search --}}
    <form
        method="GET"
        action="{{ route('admin.riders.index') }}"
        class="search"
        id="rider-search-form"
    >
        <svg
            aria-hidden="true"
            class="icon"
            viewBox="0 0 24 24"
        >
            <circle cx="11" cy="11" r="7"></circle>
            <path d="M21 21l-4.3-4.3"></path>
        </svg>

        <input
            class="input"
            id="q"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search by name, phone or email"
            autocomplete="off"
        />
    </form>


    <div id="rider-list-area">

        @include('admin.riders.partials.list')

    </div>

</div>

<!-- model -->

<div
    class="modal-backdrop"
    id="rider-details-modal"
>
    <div
        class="modal rider-details-modal"
    >

        <button
            type="button"
            class="rider-modal-close"
            id="rider-modal-close"
            aria-label="Close"
        >
            ×
        </button>


        <div id="rider-details-content">

            <div
                style="
                    padding:50px;
                    text-align:center;
                "
            >
                Loading...
            </div>

        </div>

    </div>
</div>

@endsection