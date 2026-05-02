@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.profile'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.profile'))

@section('full')

@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
    </div>
@endif

@php
    // The username registered on the Mumble server is the formatted display name
    $mumbleName = $mumbleUser->mumble_display_name ?: $mumbleUser->mumble_username;
    // Build the mumble:// URL with credentials
    $mumbleUrl = 'mumble://' . rawurlencode($mumbleName ?? '') . ':' . rawurlencode($mumbleUser->password_hash ?? '') . '@' . $server_address . ':' . $server_port . '/';
@endphp

@if($syncFailed ?? false)
<div class="alert alert-warning alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong>Not yet registered on the Mumble server!</strong>
    Your account details are ready, but we couldn't reach the Mumble bridge to register you.
    This usually means the bridge is still starting up or the database path is incorrect.
    Click the button below to try again once the bridge is online.
    <form action="{{ route('mumble::user.sync') }}" method="POST" class="mt-2 d-inline">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm">
            <i class="fas fa-sync mr-1"></i> Register on Mumble Server Now
        </button>
    </form>
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <!-- Quick Connect Card - Most Important! -->
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-headset mr-2"></i>Connect to Mumble</h3>
            </div>
            <div class="card-body text-center">
                <p class="lead">Click the button below to connect directly to Mumble with your credentials:</p>
                
                <a href="{{ $mumbleUrl }}" class="btn btn-success btn-lg mb-3">
                    <i class="fas fa-headset mr-2"></i>Open Mumble & Connect
                </a>
                
                <p class="text-muted mb-0">
                    <small>Don't have Mumble? <a href="https://www.mumble.info/downloads/" target="_blank">Download it here</a></small>
                </p>
            </div>
        </div>

        <!-- Account Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Your Account Details</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Character</dt>
                    <dd class="col-sm-8">
                        <strong>{{ $mumbleUser->seatUser->main_character->name ?? $mumbleUser->mumble_username }}</strong>
                        <small class="text-muted ml-1">(your stable identity — never changes)</small>
                    </dd>

                    <dt class="col-sm-4">Mumble Display</dt>
                    <dd class="col-sm-8">
                        <code class="h6">{{ $mumbleUser->mumble_display_name ?: $mumbleUser->mumble_username }}</code>
                        <small class="text-muted d-block">This is how you appear in Mumble. Updates when corp/tags change.</small>
                    </dd>

                    <dt class="col-sm-4">Password</dt>
                    <dd class="col-sm-8">
                        <code class="h5">{{ $mumbleUser->password_hash }}</code>
                        <button class="btn btn-sm btn-link" onclick="navigator.clipboard.writeText('{{ $mumbleUser->password_hash }}')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </dd>

                    <dt class="col-sm-4">Server</dt>
                    <dd class="col-sm-8">
                        <code>{{ $server_address }}:{{ $server_port }}</code>
                        <button class="btn btn-sm btn-link" onclick="navigator.clipboard.writeText('{{ $server_address }}')" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @if($mumbleUser->is_active)
                            <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>
                        @endif
                    </dd>

                    @if($mumbleUser->groups && count($mumbleUser->groups) > 0)
                    <dt class="col-sm-4">Groups</dt>
                    <dd class="col-sm-8">
                        @foreach($mumbleUser->groups as $group)
                            @php
                                if (str_starts_with($group, 'corp_')) {
                                    $corpId = (int) substr($group, 5);
                                    $corp = \Seat\Eveapi\Models\Corporation\CorporationInfo::find($corpId);
                                    $label = $corp ? $corp->name : $group;
                                    $badgeClass = 'badge-warning';
                                    $prefix = 'Corp';
                                } elseif (str_starts_with($group, 'alliance_')) {
                                    $allianceId = (int) substr($group, 9);
                                    $alliance = \Seat\Eveapi\Models\Alliances\Alliance::find($allianceId);
                                    $label = $alliance ? $alliance->name : $group;
                                    $badgeClass = 'badge-info';
                                    $prefix = 'Alliance';
                                } else {
                                    $label = $group;
                                    $badgeClass = 'badge-secondary';
                                    $prefix = null;
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                @if($prefix)<small>{{ $prefix }}:</small> @endif{{ $label }}
                            </span>
                        @endforeach
                    </dd>
                    @endif
                </dl>

                <hr>

                <form action="{{ route('mumble::user.reset-password') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('This will generate a new password. Your old password will stop working. Continue?')">
                        <i class="fas fa-key mr-2"></i>Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Manual Connection Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Manual Connection</h3>
            </div>
            <div class="card-body">
                <p>If the quick connect button doesn't work, use these details manually:</p>
                
                <table class="table table-sm">
                    <tr>
                        <th style="width: 120px;">Server</th>
                        <td><code>{{ $server_address }}</code></td>
                    </tr>
                    <tr>
                        <th>Port</th>
                        <td><code>{{ $server_port }}</code></td>
                    </tr>
                    <tr>
                        <th>Username</th>
                        <td><code>{{ $mumbleUser->mumble_display_name ?: $mumbleUser->mumble_username }}</code></td>
                    </tr>
                    <tr>
                        <th>Password</th>
                        <td><code>{{ $mumbleUser->password_hash }}</code></td>
                    </tr>
                </table>

                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Important:</strong> When connecting for the first time, check "Remember password" so Mumble saves it.
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Need Help?</h3>
            </div>
            <div class="card-body">
                <p><strong>First time using Mumble?</strong></p>
                <ol class="mb-0">
                    <li>Download Mumble from <a href="https://www.mumble.info/downloads/" target="_blank">mumble.info</a></li>
                    <li>Install and run it</li>
                    <li>Click the green "Open Mumble & Connect" button above</li>
                    <li>If prompted, allow Mumble to open</li>
                    <li>You should be connected!</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@stop
