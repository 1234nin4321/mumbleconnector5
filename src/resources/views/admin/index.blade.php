@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.title'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.dashboard'))

@section('full')

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Mumble Users</span>
                <span class="info-box-number">{{ $stats['total_users'] }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-sync"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Synced Today</span>
                <span class="info-box-number">{{ $stats['synced_today'] }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Sync</span>
                <span class="info-box-number">{{ $stats['pending_sync'] }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-purple elevation-1"><i class="fas fa-layer-group"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Group Mappings</span>
                <span class="info-box-number">{{ $stats['total_groups'] }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Quick Actions</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('mumble::admin.users.sync') }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sync-alt mr-2"></i>Sync All Users
                    </button>
                </form>
                
                <a href="{{ route('mumble::admin.settings') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-cog mr-2"></i>Settings
                </a>
                
                <a href="{{ route('mumble::admin.groups') }}" class="btn btn-info btn-block">
                    <i class="fas fa-layer-group mr-2"></i>Manage Groups
                </a>
                
                <a href="{{ route('mumble::admin.users') }}" class="btn btn-success btn-block">
                    <i class="fas fa-users mr-2"></i>Manage Users
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Sync Activity -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Sync Activity</h3>
                <div class="card-tools">
                    <a href="{{ route('mumble::admin.logs') }}" class="btn btn-sm btn-link">View All</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_syncs as $log)
                        <tr>
                            <td>{{ $log->user->name ?? 'Unknown' }}</td>
                            <td>{{ ucfirst($log->action) }}</td>
                            <td>
                                <span class="badge {{ $log->status_badge }}">{{ ucfirst($log->status) }}</span>
                            </td>
                            <td>{{ Str::limit($log->message, 50) }}</td>
                            <td>{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No sync activity yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@stop
