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

@endsection