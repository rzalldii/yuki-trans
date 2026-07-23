@extends('layouts.auth')
@section('title', '401 Unauthorized')
@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h1 class="display-1 fw-bold text-danger mb-0">401</h1>
                        <h4 class="mb-2">Unauthorized</h4>
                        <p class="mb-4 text-muted">You need to be logged in to access this resource.</p>
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <i class="bx bx-log-in me-1"></i>Go to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection