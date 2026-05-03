@extends('web::layouts.grids.12')

@section('title', trans('mumble::mumble.settings'))
@section('page_header', trans('mumble::mumble.title'))
@section('page_description', trans('mumble::mumble.settings'))

@section('full')

<div class="row">
    <!-- Connection Status -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Connection Status</h3>
            </div>
            <div class="card-body">
                @if($connection_status['success'])
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ $connection_status['message'] }}
                    </div>
                @elseif(isset($connection_status['is_initial']))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        {{ $connection_status['message'] }}
                    </div>
                @else
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ $connection_status['message'] }}
                    </div>
                @endif
                
                <dl class="row mb-3">
                    <dt class="col-sm-4">Connection Type</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-primary">Node.js Bridge</span>
                    </dd>
                </dl>
                
                <form action="{{ route('mumble::admin.test') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plug mr-2"></i>Test Connection
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Node.js Bridge Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Node.js Bridge Details</h3>
            </div>
            <div class="card-body">
                <p>This Mumble plugin relies entirely on a custom standalone Node.js Bridge to communicate with your Mumble Server's SQLite Database.</p>
                <p>This solves the authentication issues present with strict PBKDF2 hashing in modern Mumble versions (1.4+).</p>
                <ul>
                    <li>The Node.js bridge runs on your server (usually via PM2).</li>
                    <li>It accepts commands over a private REST API.</li>
                    <li>It directly injects users into the Mumble SQLite Database.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Settings</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('mumble::admin.settings.update') }}" method="POST">
                    @csrf
                    
                    <h5>Server Display Info</h5>
                    <div class="form-group">
                        <label for="server_address">Mumble Server Address</label>
                        <input type="text" class="form-control" name="server_address" id="server_address"
                            value="{{ $config['server']['address'] }}">
                        <small class="text-muted">Shown to users for connecting</small>
                    </div>

                    <div class="form-group">
                        <label for="server_port">Mumble Server Port</label>
                        <input type="number" class="form-control" name="server_port" id="server_port"
                            value="{{ $config['server']['port'] }}">
                    </div>

                    <hr>
                    <!-- REST Settings -->
                    <div id="rest_settings" class="driver-settings">
                        <div class="card card-outline card-success">
                            <div class="card-header"><h6 class="mb-0">Node.js Bridge API Settings</h6></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Bridge API URL</label>
                                    <input type="text" class="form-control" name="rest_url" 
                                        value="{{ $config['rest']['url'] }}" placeholder="http://127.0.0.1:8080">
                                    <small class="form-text text-muted">The internal address and port where the Node.js bridge is running (default is usually http://127.0.0.1:8080 or the internal IP of your Mumble VPS).</small>
                                </div>
                                <div class="form-group">
                                    <label>API Key (optional)</label>
                                    <input type="text" class="form-control" name="rest_api_key" 
                                        value="{{ $config['rest']['api_key'] }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5>Sync Options</h5>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Username Format</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="username_format"
                                id="username_format"
                                value="{{ old('username_format', $config['username_format'] ?? '[{ticker}] {name}') }}"
                                placeholder="[{ticker}] {name}">
                            <small class="form-text text-muted">
                                <strong>Available tokens:</strong><br>
                                <code>{name}</code> — Character name<br>
                                <code>{ticker}</code> — Alliance ticker, or corp ticker if no alliance<br>
                                <code>{corp_ticker}</code> — Corporation ticker<br>
                                <code>{alliance_ticker}</code> — Alliance ticker (blank if not in alliance)<br>
                                <br>
                                <strong>Examples:</strong><br>
                                <code>[{ticker}] {name}</code> → <em>[TRD] Pilot Name</em><br>
                                <code>{name} [{corp_ticker}]</code> → <em>Pilot Name [CORP]</em><br>
                                <code>{alliance_ticker} | {name}</code> → <em>TRD | Pilot Name</em>
                            </small>
                        </div>
                    </div>
                    <small class="text-muted">Existing usernames will update on next sync.</small>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="sync_enabled" id="sync_enabled" value="1"
                            {{ $config['sync_enabled'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_enabled">Enable Automatic Sync</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="auto_remove" id="auto_remove" value="1"
                            {{ $config['auto_remove'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_remove">Auto-remove inactive SeAT users</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="require_mapping" id="require_mapping" value="1"
                            {{ $config['require_mapping'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_mapping">Require Mumble Group Mapping</label>
                        <small class="form-text text-muted">If checked, a user MUST match at least one Active Mumble Group Mapping. If they leave the corps/alliances in your mappings, their Mumble account is instantly deleted.</small>
                    </div>

                    <hr>
                    <h5>Permission Sync</h5>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="sync_corporations" id="sync_corporations" value="1"
                            {{ $config['permissions']['sync_corporations'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_corporations">Sync Corporation Membership</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="sync_alliances" id="sync_alliances" value="1"
                            {{ $config['permissions']['sync_alliances'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_alliances">Sync Alliance Membership</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="sync_squads" id="sync_squads" value="1"
                            {{ $config['permissions']['sync_squads'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_squads">Sync SeAT Squads</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="sync_roles" id="sync_roles" value="1"
                            {{ $config['permissions']['sync_roles'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="sync_roles">Sync SeAT Roles</label>
                    </div>

                    <hr>
                    <h5>Access Control (Whitelists)</h5>
                    <p class="text-muted text-sm">Select specific Alliances or Corporations that are allowed to use Mumble. If you leave these empty, ANY active SeAT user can join. If you select any, users must belong to at least one selected Alliance OR Corporation.</p>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Allowed Alliances</label>
                        <div class="col-sm-9">
                            <select name="allowed_alliances[]" class="form-control select2" multiple="multiple" data-placeholder="Select Allowed Alliances...">
                                @foreach($alliances as $alliance)
                                    <option value="{{ $alliance->alliance_id }}" {{ in_array($alliance->alliance_id, $config['allowed_alliances']) ? 'selected' : '' }}>
                                        {{ $alliance->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Allowed Corporations</label>
                        <div class="col-sm-9">
                            <select name="allowed_corporations[]" class="form-control select2" multiple="multiple" data-placeholder="Select Allowed Corporations...">
                                @foreach($corporations as $corporation)
                                    <option value="{{ $corporation->corporation_id }}" {{ in_array($corporation->corporation_id, $config['allowed_corporations']) ? 'selected' : '' }}>
                                        {{ $corporation->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@push('javascript')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>
@endpush
