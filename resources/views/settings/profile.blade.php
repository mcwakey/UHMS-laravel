@extends('layouts.app')
@section('title', 'Profile Settings')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ __('settings.profile_settings') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-0">
                @include('settings.partials.sidebar')
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Profile Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('settings.profile_information') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center mb-3">
                                @if($user->avatar)
                                    <img src="{{ asset('storage/' . $user->avatar) }}" class="rounded-circle me-3" width="64" height="64" alt="Avatar">
                                @else
                                    <span class="avatar avatar-lg rounded-circle bg-primary text-white me-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-size:24px;">
                                        {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                    </span>
                                @endif
                                <div>
                                    <label class="form-label mb-1">{{ __('settings.profile_photo') }}</label>
                                    <input type="file" name="avatar" class="form-control form-control-sm @error('avatar') is-invalid @enderror" accept="image/*">
                                    @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">{{ __('settings.avatar_size_help') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.first_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.last_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.email_label') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.phone_label') }}</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.gender_label') }}</label>
                            <select name="gender" class="form-select">
                                <option value="">{{ __('settings.select_option') }}</option>
                                <option value="male" {{ old('gender', $user->gender?->value) === 'male' ? 'selected' : '' }}>{{ __('settings.male') }}</option>
                                <option value="female" {{ old('gender', $user->gender?->value) === 'female' ? 'selected' : '' }}>{{ __('settings.female') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.date_of_birth') }}</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">{{ __('settings.department_label') }}</label>
                            <input type="text" class="form-control" value="{{ $user->department?->name ?? 'N/A' }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">{{ __('settings.role_label') }}</label>
                            <input type="text" class="form-control" value="{{ $user->roles->first()?->name ?? 'N/A' }}" disabled>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('settings.update_profile') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('settings.change_password') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.profile.password') }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.current_password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.new_password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.confirm_new_password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning"><i class="ti ti-lock me-1"></i>{{ __('settings.update_password') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
