@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.users'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.users'))

@section('full')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Mumble Users</h3>
                <div class="card-tools">
                    <form action="{{ route('mumble::admin.users.sync') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt mr-1"></i>Sync All Users
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>SeAT User</th>
                            <th>Login Username</th>
                            <th>Display Name</th>
                            <th>Groups</th>
                            <th>Last Sync</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mumble_users as $mumbleUser)
                        <tr>
                            <td>
                                @if($mumbleUser->seatUser && $mumbleUser->seatUser->main_character)
                                    {!! img('characters', 'portrait', $mumbleUser->seatUser->main_character_id, 32, ['class' => 'img-circle eve-icon small-icon mr-2']) !!}
                                @endif
                                {{ $mumbleUser->seatUser->name ?? 'Unknown' }}
                            </td>
                            <td><code>{{ $mumbleUser->mumble_username }}</code></td>
                            <td><span class="text-primary">{{ $mumbleUser->mumble_display_name }}</span></td>
                            <td>
                                @forelse($mumbleUser->groups ?? [] as $group)
                                    @php
                                        $label = $groupLabels[$group] ?? null;
                                        if ($label) {
                                            // Corp or alliance — show name with type prefix
                                            $prefix = str_starts_with($group, 'corp_') ? 'Corp' : 'Alliance';
                                            $badgeClass = str_starts_with($group, 'alliance_') ? 'badge-info' : 'badge-warning';
                                        } else {
                                            $label = $group;
                                            $prefix = null;
                                            $badgeClass = 'badge-secondary';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}" title="{{ $group }}">
                                        @if($prefix)<small class="opacity-75">{{ $prefix }}:</small> @endif{{ $label }}
                                    </span>
                                @empty
                                    <span class="text-muted">No groups</span>
                                @endforelse
                            </td>
                            </td>
                            <td>{{ $mumbleUser->last_sync ? $mumbleUser->last_sync->diffForHumans() : 'Never' }}</td>
                            <td>
                                @if($mumbleUser->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                                @if($mumbleUser->needs_sync)
                                    <span class="badge badge-warning">Needs Sync</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('mumble::admin.users.sync.single', $mumbleUser->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info" title="Sync this user">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </form>
                                <form action="{{ route('mumble::admin.users.remove', $mumbleUser->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Remove from Mumble" onclick="return confirm('Remove this user from Mumble?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No users synced to Mumble yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($mumble_users->hasPages())
            <div class="card-footer">
                {{ $mumble_users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@if($seat_users->count() > 0)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-slash mr-2"></i>SeAT Users Not Registered on Mumble</h3>
                <div class="card-tools">
                    <span class="badge badge-warning">{{ $seat_users->count() }} unregistered</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>SeAT User</th>
                            <th>Main Character</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seat_users as $seatUser)
                        <tr>
                            <td>{{ $seatUser->name }}</td>
                            <td>{{ $seatUser->main_character->name ?? '—' }}</td>
                            <td>
                                <form action="{{ route('mumble::admin.users.force-register') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="seat_user_id" value="{{ $seatUser->id }}">
                                    <button type="submit" class="btn btn-sm btn-warning"
                                        onclick="return confirm('Force-register {{ $seatUser->name }} on Mumble? This bypasses the alliance whitelist check.')"
                                        title="Force-register bypassing whitelist (use for returning members with stale ESI data)">
                                        <i class="fas fa-user-plus mr-1"></i> Force Register
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

@stop
