<?php

namespace Seat\MumbleConnector\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\MumbleConnector\Models\MumbleTemporaryLink;
use Seat\MumbleConnector\Services\MumbleService;

class MumbleUserController extends Controller
{
    protected MumbleService $mumbleService;

    public function __construct(MumbleService $mumbleService)
    {
        $this->mumbleService = $mumbleService;
    }

    /**
     * Helper to get setting from DB.
     */
    protected function getSetting($name, $default = null)
    {
        $row = \DB::table('global_settings')->where('name', $name)->first();
        return $row ? $row->value : $default;
    }

    /**
     * Display user's Mumble profile.
     * Auto-creates account if user doesn't have one.
     */
    public function profile()
    {
        $user = Auth::user();
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();
        $justCreated = false;
        $syncFailed  = false;

        // Auto-create account if user doesn't have one
        if (!$mumbleUser || !$mumbleUser->mumble_username) {
            [$mumbleUser, $syncFailed] = $this->createMumbleAccount($user);
            $justCreated = true;
        } elseif (!$mumbleUser->mumble_user_id) {
            // Account exists locally but was never successfully pushed to bridge
            $syncFailed = true;
        }
        
        // Get server info from settings
        $server_address = $this->getSetting('mumble.server_address', config('seat.mumble.server.address', 'mumble.example.com'));
        $server_port = $this->getSetting('mumble.server_port', config('seat.mumble.server.port', 64738));

        return view('mumble::user.profile', compact('user', 'mumbleUser', 'server_address', 'server_port', 'justCreated', 'syncFailed'));
    }

    /**
     * Create a Mumble account for the user.
     * Returns [MumbleUser, $syncFailed]
     */
    protected function createMumbleAccount($user): array
    {
        // Use MumbleService so format (including ticker) is applied consistently
        $username = $this->mumbleService->generateUsername($user);

        // Generate a random password
        $password = Str::random(16);

        // Create or update the MumbleUser record
        $mumbleUser = MumbleUser::updateOrCreate(
            ['seat_user_id' => $user->id],
            [
                'mumble_username' => $username,
                'password_hash'   => $password,
                'is_active'       => true,
                'last_sync'       => now(),
            ]
        );

        // Sync to Mumble server — track if it worked
        $syncFailed = false;
        try {
            $synced = $this->mumbleService->syncUser($user);
            if (!$synced) {
                $syncFailed = true;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to sync new Mumble user: ' . $e->getMessage());
            $syncFailed = true;
        }

        // Reload so mumble_user_id is updated if sync succeeded
        $mumbleUser->refresh();

        return [$mumbleUser, $syncFailed];
    }

    /**
     * Register/create Mumble account for the user.
     */
    public function register()
    {
        $user = Auth::user();
        
        // Check if user already has an account
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();
        
        if ($mumbleUser && $mumbleUser->mumble_username) {
            return redirect()
                ->route('mumble::user.profile')
                ->with('info', 'You already have a Mumble account.');
        }

        // Use MumbleService so format (including ticker) is applied consistently
        $username = $this->mumbleService->generateUsername($user);

        // Generate a random password
        $password = Str::random(16);

        // Create or update the MumbleUser record
        $mumbleUser = MumbleUser::updateOrCreate(
            ['seat_user_id' => $user->id],
            [
                'mumble_username' => $username,
                'password_hash'   => $password,
                'is_active'       => true,
                'last_sync'       => now(),
            ]
        );

        // Sync to Mumble server
        try {
            $this->mumbleService->syncUser($user);
        } catch (\Exception $e) {
            \Log::warning('Failed to sync new Mumble user: ' . $e->getMessage());
        }

        return redirect()
            ->route('mumble::user.profile')
            ->with('success', 'Your Mumble account has been created! Your password is shown below - save it now, you won\'t see it again!');
    }

    /**
     * Reset/regenerate user's password.
     */
    public function resetPassword()
    {
        $user = Auth::user();
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();

        if (!$mumbleUser) {
            return redirect()
                ->route('mumble::user.profile')
                ->with('error', 'You need to register first.');
        }

        // Generate new password
        $password = Str::random(16);
        
        $mumbleUser->update([
            'password_hash' => $password,
            'last_sync' => now(),
        ]);

        // Sync to Mumble server
        try {
            $this->mumbleService->syncUser($user);
        } catch (\Exception $e) {
            \Log::warning('Failed to sync Mumble password reset: ' . $e->getMessage());
        }

        return redirect()
            ->route('mumble::user.profile')
            ->with('success', 'Your password has been reset! Your new password is shown below - save it now!');
    }

    /**
     * Re-sync the user to the Mumble server (useful when bridge was down on registration).
     */
    public function syncToServer()
    {
        $user = Auth::user();
        $mumbleUser = MumbleUser::where('seat_user_id', $user->id)->first();

        if (!$mumbleUser) {
            return redirect()
                ->route('mumble::user.profile')
                ->with('error', 'No Mumble account found. Please reload the page.');
        }

        try {
            $synced = $this->mumbleService->syncUser($user);
            $mumbleUser->refresh();

            if ($synced && $mumbleUser->mumble_user_id) {
                return redirect()
                    ->route('mumble::user.profile')
                    ->with('success', 'Your account was successfully registered on the Mumble server! You can now connect using the password shown below.');
            } else {
                return redirect()
                    ->route('mumble::user.profile')
                    ->with('error', 'Could not reach the Mumble bridge. Please try again later or contact an admin.');
            }
        } catch (\Exception $e) {
            \Log::warning('User sync to Mumble failed: ' . $e->getMessage());
            return redirect()
                ->route('mumble::user.profile')
                ->with('error', 'Could not reach the Mumble bridge: ' . $e->getMessage());
        }
    }


    /**
     * Sanitize character name for use as Mumble username.
     */
    protected function sanitizeUsername(string $name): string
    {
        // Remove special characters, keep alphanumeric and underscores
        $username = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        
        // Ensure it doesn't start with a number
        if (is_numeric(substr($username, 0, 1))) {
            $username = '_' . $username;
        }
        
        // Limit length
        return substr($username, 0, 32);
    }

    /**
     * Display guest connection link details.
     */
    public function guestLink($token)
    {
        $link = MumbleTemporaryLink::where('token', $token)->first();

        if (!$link) {
            abort(404, 'Invalid link.');
        }

        if ($link->isExpired()) {
            return view('mumble::guest.expired');
        }

        // Get server info from settings
        $server_address = $this->getSetting('mumble.server_address', config('seat.mumble.server.address', 'mumble.example.com'));
        $server_port = $this->getSetting('mumble.server_port', config('seat.mumble.server.port', 64738));

        return view('mumble::guest.link', compact('link', 'server_address', 'server_port'));
    }
}
