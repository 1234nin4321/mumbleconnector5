<?php

namespace Seat\MumbleConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\MumbleConnector\Models\MumbleGroupMapping;
use Seat\MumbleConnector\Models\MumbleSyncLog;
use Seat\MumbleConnector\Models\MumbleTemporaryLink;
use Seat\MumbleConnector\Services\MumbleService;
use Seat\MumbleConnector\Jobs\SyncAllMumbleUsers;
use Seat\MumbleConnector\Jobs\SyncSingleMumbleUser;
use Seat\Web\Models\User;
use Seat\Web\Models\Squads\Squad;
use Illuminate\Support\Str;

class MumbleAdminController extends Controller
{
    protected MumbleService $mumbleService;

    public function __construct(MumbleService $mumbleService)
    {
        $this->mumbleService = $mumbleService;
    }

    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => MumbleUser::count(),
            'synced_today' => MumbleUser::whereDate('last_sync', today())->count(),
            'pending_sync' => MumbleUser::where('needs_sync', true)->count(),
            'total_groups' => MumbleGroupMapping::count(),
        ];

        $recent_syncs = MumbleSyncLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('mumble::admin.index', compact('stats', 'recent_syncs'));
    }

    /**
     * Display settings page.
     */
    public function settings()
    {
        // Helper function to get setting from DB
        $getSetting = function($name, $default = null) {
            $row = \DB::table('global_settings')->where('name', $name)->first();
            return $row ? $row->value : $default;
        };

        // Get base config
        $config = config('seat.mumble');
        
        // Fetch saved settings from DB to override config
        $settings = [
            'server' => [
                'address' => $getSetting('mumble.server_address', $config['server']['address'] ?? ''),
                'port'    => $getSetting('mumble.server_port', $config['server']['port'] ?? '64738'),
            ],
            'driver'         => 'rest',
            'sync_enabled'   => (bool) $getSetting('mumble.sync_enabled', '1'),
            'auto_remove'    => (bool) $getSetting('mumble.auto_remove', '1'),
            
            'rest' => [
                'url'     => $getSetting('mumble.rest_url', $config['rest']['url']),
                'api_key' => $getSetting('mumble.rest_api_key', $config['rest']['api_key']),
            ],
            
            'permissions' => [
                'sync_corporations' => (bool) $getSetting('mumble.sync_corporations', '1'),
                'sync_alliances'    => (bool) $getSetting('mumble.sync_alliances', '1'),
                'sync_squads'       => (bool) $getSetting('mumble.sync_squads', '1'),
                'sync_roles'        => (bool) $getSetting('mumble.sync_roles', '1'),
            ]
        ];

        // Allowed alliances and corporations
        $allowedAlliances = array_filter(explode(',', $getSetting('mumble.allowed_alliances', '')));
        $allowedCorporations = array_filter(explode(',', $getSetting('mumble.allowed_corporations', '')));
        $settings['allowed_alliances'] = $allowedAlliances;
        $settings['allowed_corporations'] = $allowedCorporations;

        // Fetch all alliances and corporations
        $alliances = \Seat\Eveapi\Models\Alliances\Alliance::orderBy('name')->get();
        $corporations = \Seat\Eveapi\Models\Corporation\CorporationInfo::orderBy('name')->get();

        // Check if we have a saved server address to determine if configured
        $saved_address = $getSetting('mumble.server_address');
        $is_configured = !empty($saved_address);

        if ($is_configured || session()->has('test_result')) {
            $connection_status = session('test_result') ?? $this->mumbleService->testConnection();
        } else {
            $connection_status = [
                'success' => false,
                'message' => 'Plugin not yet configured. Please enter your Mumble details and click Save.',
                'driver' => 'None',
                'is_initial' => true,
            ];
        }

        return view('mumble::admin.settings', [
            'config' => $settings,
            'connection_status' => $connection_status,
            'is_configured' => $is_configured,
            'alliances' => $alliances,
            'corporations' => $corporations,
        ]);
    }
    

    /**
     * Update settings.
     */
    public function updateSettings(Request $request)
    {
        // Define all settings to save with their values
        // Use ?? '' to handle Laravel's ConvertEmptyStringsToNull middleware
        $settingsToSave = [
            'mumble.server_address' => $request->input('server_address') ?? '',
            'mumble.server_port'    => (string) ($request->input('server_port') ?? '64738'),
            'mumble.rest_url'       => $request->input('rest_url') ?? '',
            'mumble.rest_api_key'   => $request->input('rest_api_key') ?? '',
            'mumble.username_format' => $request->input('username_format') ?? 'main_character',
            'mumble.sync_enabled'   => $request->has('sync_enabled') ? '1' : '0',
            'mumble.auto_remove'    => $request->has('auto_remove') ? '1' : '0',
            'mumble.require_mapping'=> $request->has('require_mapping') ? '1' : '0',
            'mumble.sync_corporations' => $request->has('sync_corporations') ? '1' : '0',
            'mumble.sync_alliances' => $request->has('sync_alliances') ? '1' : '0',
            'mumble.sync_squads'    => $request->has('sync_squads') ? '1' : '0',
            'mumble.sync_roles'     => $request->has('sync_roles') ? '1' : '0',
            'mumble.allowed_alliances'    => implode(',', $request->input('allowed_alliances', [])),
            'mumble.allowed_corporations' => implode(',', $request->input('allowed_corporations', [])),
        ];

        // Store each setting directly in the database
        foreach ($settingsToSave as $name => $value) {
            \DB::table('global_settings')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'value' => $value]
            );
        }

        return redirect()
            ->route('mumble::admin.settings')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Display group mappings.
     */
    public function groups()
    {
        $mappings = MumbleGroupMapping::orderBy('seat_type')->orderBy('seat_identifier')->get();
        $squads = Squad::orderBy('name')->get();

        return view('mumble::admin.groups', compact('mappings', 'squads'));
    }

    /**
     * Add a group mapping.
     */
    public function addGroupMapping(Request $request)
    {
        $validated = $request->validate([
            'seat_type'       => 'required|in:role,squad,corporation,alliance',
            'seat_identifier' => 'required|string',
            'mumble_group'    => 'required|string',
            'name_tag'        => 'nullable|string|max:50',
        ]);

        MumbleGroupMapping::updateOrCreate(
            [
                'seat_type'       => $validated['seat_type'],
                'seat_identifier' => $validated['seat_identifier'],
            ],
            [
                'mumble_group' => $validated['mumble_group'],
                'name_tag'     => $validated['name_tag'] ?? null,
            ]
        );

        return redirect()
            ->route('mumble::admin.groups')
            ->with('success', 'Group mapping added successfully.');
    }

    /**
     * Delete a group mapping.
     */
    public function deleteGroupMapping($id)
    {
        MumbleGroupMapping::findOrFail($id)->delete();

        return redirect()
            ->route('mumble::admin.groups')
            ->with('success', 'Group mapping removed.');
    }

    /**
     * Display users list.
     */
    public function users()
    {
        $mumble_users = MumbleUser::with('seatUser')->orderBy('mumble_username')->paginate(50);
        $seat_users = User::whereNotIn('id', MumbleUser::pluck('seat_user_id'))->get();

        // Collect all corp and alliance IDs referenced in groups across all users
        $corpIds     = [];
        $allianceIds = [];

        foreach ($mumble_users as $mu) {
            foreach ($mu->groups ?? [] as $group) {
                if (str_starts_with($group, 'corp_')) {
                    $corpIds[] = (int) substr($group, 5);
                } elseif (str_starts_with($group, 'alliance_')) {
                    $allianceIds[] = (int) substr($group, 9);
                }
            }
        }

        // Batch look up names (two queries total)
        $corpNames     = \Seat\Eveapi\Models\Corporation\CorporationInfo::whereIn('corporation_id', array_unique($corpIds))
            ->pluck('name', 'corporation_id');
        $allianceNames = \Seat\Eveapi\Models\Alliances\Alliance::whereIn('alliance_id', array_unique($allianceIds))
            ->pluck('name', 'alliance_id');

        // Build a flat map: 'corp_98791891' => 'The Corp Name'
        $groupLabels = [];
        foreach ($corpNames as $id => $name) {
            $groupLabels["corp_{$id}"] = $name;
        }
        foreach ($allianceNames as $id => $name) {
            $groupLabels["alliance_{$id}"] = $name;
        }

        return view('mumble::admin.users', compact('mumble_users', 'seat_users', 'groupLabels'));
    }

    /**
     * Sync all users.
     */
    public function syncAllUsers()
    {
        SyncAllMumbleUsers::dispatch();

        return redirect()
            ->route('mumble::admin.users')
            ->with('success', 'User sync job has been queued.');
    }

    /**
     * Sync a single user.
     */
    public function syncUser($id)
    {
        $mumbleUser = MumbleUser::findOrFail($id);
        SyncSingleMumbleUser::dispatch($mumbleUser->seatUser);

        return redirect()
            ->route('mumble::admin.users')
            ->with('success', 'User sync job has been queued.');
    }

    /**
     * Force-register a SeAT user onto Mumble, bypassing the whitelist check.
     * Used by admins when ESI affiliation data is stale after a user rejoins.
     */
    public function forceRegisterUser(Request $request)
    {
        $seatUserId = $request->input('seat_user_id');
        $seatUser   = User::findOrFail($seatUserId);

        // Get or create the MumbleUser record
        $mumbleUser = MumbleUser::firstOrNew(['seat_user_id' => $seatUser->id]);

        // Always generate a password if missing
        if (empty($mumbleUser->password_hash)) {
            $mumbleUser->password_hash = \Illuminate\Support\Str::random(16);
        }

        // Generate the username/display name
        $username    = $this->mumbleService->generateUsername($seatUser);
        $displayName = $this->mumbleService->generateDisplayName($seatUser);
        $groups      = $this->mumbleService->calculateUserGroups($seatUser);

        $mumbleUser->fill([
            'mumble_username'     => $username,
            'mumble_display_name' => $displayName,
            'groups'              => $groups,
            'is_active'           => true,
            'last_sync'           => now(),
        ]);
        $mumbleUser->save();

        // Push to bridge directly, bypassing whitelist
        try {
            $driver = $this->mumbleService->getDriver();
            $result = $driver->syncUser($mumbleUser, $groups);

            if ($result) {
                $mumbleUser->refresh();
                return redirect()
                    ->route('mumble::admin.users')
                    ->with('success', "User [{$username}] has been force-registered on Mumble.");
            }

            return redirect()
                ->route('mumble::admin.users')
                ->with('error', "Account created in SeAT but the Mumble bridge returned an error. Check that the bridge is running.");
        } catch (\Exception $e) {
            return redirect()
                ->route('mumble::admin.users')
                ->with('error', 'Bridge error: ' . $e->getMessage());
        }
    }

    /**
     * Remove a user from Mumble.
     */
    public function removeUser($id)
    {
        $mumbleUser = MumbleUser::findOrFail($id);
        
        $this->mumbleService->removeUser($mumbleUser);
        $mumbleUser->delete();

        return redirect()
            ->route('mumble::admin.users')
            ->with('success', 'User removed from Mumble.');
    }

    /**
     * Display logs.
     */
    public function logs()
    {
        $logs = MumbleSyncLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return view('mumble::admin.logs', compact('logs'));
    }

    /**
     * Test connection to Mumble server.
     */
    public function testConnection()
    {
        $result = $this->mumbleService->testConnection();

        return redirect()
            ->route('mumble::admin.settings')
            ->with('test_result', $result);
    }

    /**
     * Display temporary connection links.
     */
    public function temporaryLinks()
    {
        $links = MumbleTemporaryLink::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('mumble::admin.links', compact('links'));
    }

    /**
     * Create a new temporary connection link.
     */
    public function createTemporaryLink(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'duration'     => 'required|integer|min:1|max:168', // up to a week in hours
        ]);

        $token = Str::random(32);
        $password = Str::random(16);
        
        // Sanitize display name for Mumble username
        $mumbleUsername = 'Guest_' . preg_replace('/[^a-zA-Z0-9]/', '', $validated['display_name']) . '_' . Str::random(4);

        $link = MumbleTemporaryLink::create([
            'token'           => $token,
            'display_name'    => $validated['display_name'],
            'mumble_username' => $mumbleUsername,
            'password'        => $password,
            'expires_at'      => now()->addHours($validated['duration']),
            'created_by'      => auth()->id(),
        ]);

        // Attempt to create the user on Mumble server immediately
        try {
            $driver = $this->mumbleService->getDriver();
            
            // Mock a MumbleUser-like object or just use the driver directly if I modify it.
            // For now, let's see if I can just pass a fake MumbleUser.
            $fakeUser = new MumbleUser([
                'mumble_username'     => $mumbleUsername,
                'mumble_display_name' => $validated['display_name'] . ' (Guest)',
                'password_hash'       => $password,
            ]);
            
            $success = $driver->syncUser($fakeUser, ['guests']);
            
            if ($success && $fakeUser->mumble_user_id) {
                $link->update(['mumble_user_id' => $fakeUser->mumble_user_id]);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to pre-create Mumble guest user: ' . $e->getMessage());
        }

        return redirect()
            ->route('mumble::admin.links')
            ->with('success', 'Temporary connection link created.');
    }

    /**
     * Delete a temporary connection link.
     */
    public function deleteTemporaryLink($id)
    {
        $link = MumbleTemporaryLink::findOrFail($id);

        // Remove user from Mumble if they were created
        if ($link->mumble_user_id) {
            try {
                $driver = $this->mumbleService->getDriver();
                $fakeUser = new MumbleUser([
                    'mumble_user_id' => $link->mumble_user_id,
                ]);
                $driver->removeUser($fakeUser);
            } catch (\Exception $e) {
                \Log::warning('Failed to remove Mumble guest user on deletion: ' . $e->getMessage());
            }
        }

        $link->delete();

        return redirect()
            ->route('mumble::admin.links')
            ->with('success', 'Temporary link deleted.');
    }
}
