@php
    $initials = collect(
        preg_split(
            '/\s+/',
            trim($driver->full_name)
        )
    )
    ->filter()
    ->take(2)
    ->map(function ($part) {
        return strtoupper(
            substr($part, 0, 1)
        );
    })
    ->implode('');


    $statusLabel =
        $driver->status === 'under_review'
            ? 'Pending'
            : ucfirst($driver->status);


    $statusClass = match(
        $driver->status
    ) {
        'approved' =>
            'tone-green',

        'rejected' =>
            'tone-red',

        default =>
            'tone-amber',
    };
@endphp


<div class="driver-detail-header">

    <div class="driver-detail-avatar">
        {{ $initials }}
    </div>


    <div>

        <div class="driver-detail-name">

            <h2>
                {{ $driver->full_name }}
            </h2>

            <span
                class="badge {{ $statusClass }}"
            >
                {{ $statusLabel }}
            </span>

        </div>

    </div>

</div>


<p
    class="muted"
    style="margin-top:0;"
>
    Full registration details submitted by the driver.
</p>


<div class="driver-detail-grid">


    {{-- ACCOUNT --}}
    <div class="driver-detail-section full">

        <div class="driver-detail-title">
            Account
        </div>


        <div class="driver-info-grid cols-3">

            <div class="driver-info-item">

                <span class="driver-info-label">
                    Email
                </span>

                <span class="driver-info-value">
                    {{ $driver->email }}
                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Phone
                </span>

                <span class="driver-info-value">

                    {{ $driver->country_code }}

                    {{ $driver->phone_number }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Joined
                </span>

                <span class="driver-info-value">

                    {{ \Carbon\Carbon::parse(
                        $driver->created_at
                    )->format('Y-m-d') }}

                </span>

            </div>

        </div>

    </div>


    {{-- PERSONAL INFORMATION --}}
    <div class="driver-detail-section">

        <div class="driver-detail-title">
            Personal Information
        </div>


        <div class="driver-info-grid">

            <div class="driver-info-item">

                <span class="driver-info-label">
                    Full Name
                </span>

                <span class="driver-info-value">
                    {{ $driver->full_name }}
                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Date of Birth
                </span>

                <span class="driver-info-value">

                    @if($driver->date_of_birth)

                        {{ \Carbon\Carbon::parse(
                            $driver->date_of_birth
                        )->format('d M Y') }}

                    @else
                        -
                    @endif

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Gender
                </span>

                <span class="driver-info-value">

                    {{
                        $driver->gender
                            ? ucfirst($driver->gender)
                            : '-'
                    }}

                </span>

            </div>

        </div>

    </div>


    {{-- HOME ADDRESS --}}
    <div class="driver-detail-section">

        <div class="driver-detail-title">
            Home Address
        </div>


        <div class="driver-info-grid">

            <div
                class="driver-info-item"
                style="grid-column:1/-1;"
            >

                <span class="driver-info-label">
                    Address
                </span>

                <span class="driver-info-value">

                    {{ $driver->address ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    City
                </span>

                <span class="driver-info-value">

                    {{ $driver->city ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    State
                </span>

                <span class="driver-info-value">

                    {{ $driver->state ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    ZIP Code
                </span>

                <span class="driver-info-value">

                    {{ $driver->zip_code ?: '-' }}

                </span>

            </div>

        </div>

    </div>


    {{-- CAR INFORMATION --}}
    <div class="driver-detail-section">

        <div class="driver-detail-title">
            Car Information
        </div>


        <div class="driver-info-grid">

            <div class="driver-info-item">

                <span class="driver-info-label">
                    Make
                </span>

                <span class="driver-info-value">

                    {{ $driver->car_make ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Model
                </span>

                <span class="driver-info-value">

                    {{ $driver->car_model ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Year
                </span>

                <span class="driver-info-value">

                    {{ $driver->car_year ?: '-' }}

                </span>

            </div>


            <div class="driver-info-item">

                <span class="driver-info-label">
                    Color
                </span>

                <span class="driver-info-value">

                    {{ $driver->car_color ?: '-' }}

                </span>

            </div>

        </div>

    </div>


    {{-- LICENSE & PLATES --}}
    <div class="driver-detail-section">

        <div class="driver-detail-title">
            License & Plates
        </div>


        <div class="driver-info-grid">

            <div class="driver-info-item">

                <span class="driver-info-label">
                    License Plate
                </span>

                <span class="driver-info-value">

                    {{
                        $driver
                            ->license_plate_number
                            ?: '-'
                    }}

                </span>

            </div>

        </div>

    </div>


    {{-- DOCUMENTS --}}
    <div class="driver-detail-section full">

        <div class="driver-detail-title">
            Documents
        </div>


        @php
            $requiredDocuments = [
                'driver_license'
                    => 'Driver License',

                'car_registration'
                    => 'Car Registration',

                'car_insurance'
                    => 'Car Insurance',

                'car_inspection'
                    => 'Car Inspection',

                'work_id'
                    => 'Work ID',
            ];


            $uploadedTypes =
                $documents
                    ->pluck('document_type')
                    ->toArray();


            $missingCount = 0;

            foreach (
                $requiredDocuments
                as $type => $label
            ) {

                if (
                    !in_array(
                        $type,
                        $uploadedTypes
                    )
                ) {
                    $missingCount++;
                }

            }
        @endphp


        @if($missingCount > 0)

            <p
                style="
                    color:#ef4444;
                    margin:0 0 10px;
                "
            >
                {{ $missingCount }}
                document(s) not uploaded yet.
            </p>

        @else

            <p
                style="
                    color:#16a34a;
                    margin:0 0 10px;
                "
            >
                All required documents uploaded.
            </p>

        @endif


        <div class="document-badges">

            @foreach(
                $requiredDocuments
                as $type => $label
            )

                @php
                    $uploaded =
                        in_array(
                            $type,
                            $uploadedTypes
                        );
                @endphp


                <span
                    class="document-chip
                    {{
                        $uploaded
                            ? 'uploaded'
                            : 'missing'
                    }}"
                >
                    ▣

                    {{ $label }}

                    ·

                    {{
                        $uploaded
                            ? 'Uploaded'
                            : 'Missing'
                    }}

                </span>

            @endforeach

        </div>

    </div>


    {{-- PAYMENT METHODS --}}
    <div class="driver-detail-section full">

        <div class="driver-detail-title">
            Payment Methods Accepted
        </div>


        <div class="payment-method-badges">

            @forelse(
                $paymentMethods
                as $method
            )

                <span class="payment-chip">

                    ▣ {{ $method }}

                </span>

            @empty

                <span class="muted">
                    No payment methods selected.
                </span>

            @endforelse

        </div>

    </div>

</div>


@if($driver->status === 'under_review')

    <div class="driver-modal-actions">

        <button
            type="button"
            class="btn btn-outline driver-status-btn"
            data-id="{{ $driver->id }}"
            data-status="rejected"
        >
            Reject
        </button>


        <button
            type="button"
            class="btn driver-status-btn"
            data-id="{{ $driver->id }}"
            data-status="approved"
        >
            Approve
        </button>

    </div>

@endif