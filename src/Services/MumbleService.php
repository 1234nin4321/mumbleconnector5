<?php

namespace Seat\MumbleConnector\Services;

use Illuminate\Support\Facades\Log;
use Seat\MumbleConnector\Contracts\MumbleDriverInterface;
use Seat\MumbleConnector\Drivers\RestDriver;
use Seat\MumbleConnector\Models\MumbleUser;
use Seat\MumbleConnector\Models\MumbleGroupMapping;
use Seat\MumbleConnector\Models\MumbleSyncLog;
use Seat\Web\Models\User;
use Exception;

class MumbleService
{
    protected MumbleDriverInterface $driver;
    protected string $driverType;

    public function __construct()
    {
        // Read driver from database directly
        $this->driverType = $this->getSetting('mumble.driver', config('seat.mumble.driver', 'rest'));
        $this->driver = $this->createDriver($this->driverType);
    }

    /**
     * Read a setting directly from DB (avoids SeAT setting() helper caching/serialization issues).
     */
    protected function getSetting(string $name, $default = null)
    {
        $row = \DB::table('global_settings')->where('name', $name)->first();
        return $row ? $row->value : $default;
    }

    /**
     * Create the appropriate driver based on configuration.
     */
    protected function createDriver(string $type): MumbleDriverInterface
    {
        return match ($type) {
            'rest' => new RestDriver(),
            default => new RestDriver(),
        };
    }

    /**
     * Get the current driver.
     */
    public function getDriver(): MumbleDriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the current driver type.
     */
    public function getDriverType(): string
    {
        return $this->driverType;
    }

    /**
     * Switch to a different driver (for testing).
     */
    public function useDriver(string $type): self
    {
        $this->driverType = $type;
        $this->driver = $this->createDriver($type);
        return $this;
    }

    /**
     * Test connection to Mumble server.
     */
    public function testConnection(): array
    {
        return $this->driver->testConnection();
    }

    /**
     * Get server info.
     */
    public function getServerInfo(): array
    {
        return $this->driver->getServerInfo();
    }

    /**
     * Sync a user to Mumble.
     */
    public function syncUser(User $user): bool
    {
        try {
            $mumbleUser = MumbleUser::firstOrNew(['seat_user_id' => $user->id]);
            
            // Check if user should be removed
            $autoRemove     = (bool) $this->getSetting('mumble.auto_remove', true);
            $requireMapping = (bool) $this->getSetting('mumble.require_mapping', false);

            $hasMapping = false;
            $mappings = MumbleGroupMapping::active()->get();
            foreach ($mappings as $mapping) {
                if ($this->userMatchesMapping($user, $mapping)) {
                    $hasMapping = true;
                    break;
                }
            }

            // Check whitelist (Allowed Alliances / Allowed Corporations)
            $allowedAlliances = array_filter(explode(',', $this->getSetting('mumble.allowed_alliances', '')));
            $allowedCorps     = array_filter(explode(',', $this->getSetting('mumble.allowed_corporations', '')));
            
            $whitelistPass = true;
            if (!empty($allowedAlliances) || !empty($allowedCorps)) {
                $whitelistPass = false;
                $mainChar = $user->main_character;
                
                if ($mainChar && $mainChar->affiliation) {
                    if (in_array($mainChar->affiliation->alliance_id, $allowedAlliances) || 
                        in_array($mainChar->affiliation->corporation_id, $allowedCorps)) {
                        $whitelistPass = true;
                    }
                }
                
                // Fallback: check all characters on account if main char doesn't match
                if (!$whitelistPass) {
                    foreach ($user->characters as $char) {
                        if ($char->affiliation && (
                            in_array($char->affiliation->alliance_id, $allowedAlliances) ||
                            in_array($char->affiliation->corporation_id, $allowedCorps)
                        )) {
                            $whitelistPass = true;
                            break;
                        }
                    }
                }
            }

            // If user is inactive in SeAT, or we require a mapping and they have none, or they fail whitelist
            if (($autoRemove && !$user->active) || ($requireMapping && !$hasMapping) || !$whitelistPass) {
                if ($mumbleUser->exists && $mumbleUser->mumble_user_id) {
                    $this->removeUser($mumbleUser);
                    $mumbleUser->delete();
                }
                return false;
            }

            // Stable character name — never changes
            $username    = $this->generateUsername($user);
            // Formatted display name — ticker, tags etc. — sent to Mumble server
            $displayName = $this->generateDisplayName($user);
            $groups      = $this->calculateUserGroups($user);

            $oldGroups = $mumbleUser->groups ?? [];

            // Update local record
            $fillData = [
                'mumble_username'     => $username,     // character name, stable
                'mumble_display_name' => $displayName,  // formatted, pushed to Mumble
                'groups'              => $groups,
                'is_active'           => true,
            ];

            // Generate a password if the user doesn't have one yet
            // (covers existing SeAT users whose record was created without a password)
            if (empty($mumbleUser->password_hash)) {
                $fillData['password_hash'] = \Illuminate\Support\Str::random(16);
            }

            $mumbleUser->fill($fillData);
            $mumbleUser->save();

            // Sync to Mumble server via driver
            $success = $this->driver->syncUser($mumbleUser, $groups);

            if ($success) {
                $mumbleUser->syncComplete();

                MumbleSyncLog::logSync(
                    $user->id,
                    'sync',
                    'success',
                    "User synced via {$this->driverType} driver",
                    $oldGroups,
                    $groups
                );
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Mumble sync failed', [
                'user_id' => $user->id,
                'driver' => $this->driverType,
                'error' => $e->getMessage(),
            ]);

            MumbleSyncLog::logSync(
                $user->id,
                'sync',
                'error',
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Remove a user from Mumble.
     */
    public function removeUser(MumbleUser $mumbleUser): bool
    {
        try {
            $success = $this->driver->removeUser($mumbleUser);

            if ($success) {
                MumbleSyncLog::logSync(
                    $mumbleUser->seat_user_id,
                    'remove',
                    'success',
                    'User removed from Mumble'
                );
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Mumble user removal failed', [
                'mumble_user_id' => $mumbleUser->id,
                'driver' => $this->driverType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate username from SeAT user.
     */
    /**
     * Generate the stable Mumble username — always just the character name.
     * This never changes regardless of corp/alliance/tag changes.
     */
    public function generateUsername(User $user): string
    {
        $character = $user->main_character;
        return $character->name ?? "User_{$user->id}";
    }

    /**
     * Generate the Mumble display name — the formatted name shown in the Mumble client.
     * Includes ticker prefix/suffix and any group mapping name tags.
     * This is what gets registered on the Mumble server.
     */
    public function generateDisplayName(User $user): string
    {
        // Read format template from DB, fall back to config default
        $row = \DB::table('global_settings')->where('name', 'mumble.username_format')->first();
        $template = $row ? $row->value : config('seat.mumble.sync.username_format', '[{ticker}] {name}');

        // Backward compatibility: convert old preset keys to templates
        $legacyMap = [
            'ticker_name'    => '[{ticker}] {name}',
            'name_ticker'    => '{name} [{ticker}]',
            'character_name' => '{name}',
            'main_character' => '{name}',
        ];
        if (isset($legacyMap[$template])) {
            $template = $legacyMap[$template];
        }

        $character      = $user->main_character;
        $name           = $character->name ?? "User_{$user->id}";
        $corpTicker     = null;
        $allianceTicker = null;

        if ($character && $character->affiliation) {
            if ($character->affiliation->corporation_id) {
                $corp = \Seat\Eveapi\Models\Corporation\CorporationInfo::find($character->affiliation->corporation_id);
                $corpTicker = $corp->ticker ?? null;
            }
            if ($character->affiliation->alliance_id) {
                $alliance = \Seat\Eveapi\Models\Alliances\Alliance::find($character->affiliation->alliance_id);
                $allianceTicker = $alliance->ticker ?? null;
            }
        }

        $ticker = $allianceTicker ?? $corpTicker ?? '';

        $displayName = str_replace(
            ['{name}', '{corp_ticker}', '{alliance_ticker}', '{ticker}'],
            [$name,    $corpTicker ?? '', $allianceTicker ?? '', $ticker],
            $template
        );

        // Append name_tags from matched group mappings
        $mappings = MumbleGroupMapping::active()->whereNotNull('name_tag')->where('name_tag', '!=', '')->get();
        $tags = [];
        foreach ($mappings as $mapping) {
            if ($this->userMatchesMapping($user, $mapping)) {
                $tags[] = $mapping->name_tag;
            }
        }
        if (!empty($tags)) {
            $displayName .= implode('', $tags);
        }

        // Sanitize: allow alphanumeric, spaces, brackets, pipes, hyphens, underscores, dots, apostrophes
        $displayName = preg_replace('/[^a-zA-Z0-9\s\[\]\|\-_\.\:\']/u', '', $displayName);

        return trim($displayName);
    }



    /**
     * Calculate which Mumble groups a user should be in.
     */
    public function calculateUserGroups(User $user): array
    {
        $groups = [];
        $mappings = MumbleGroupMapping::active()->get();

        // Add default group
        $defaultGroup = config('seat.mumble.sync.default_group');
        if ($defaultGroup) {
            $groups[] = $defaultGroup;
        }

        foreach ($mappings as $mapping) {
            if ($this->userMatchesMapping($user, $mapping)) {
                $groups[] = $mapping->mumble_group;
            }
        }

        // Use only the MAIN character's corp/alliance for groups
        $mainCharacter = $user->main_character;

        if ($mainCharacter && $mainCharacter->affiliation) {
            // Add corporation group if enabled
            if (config('seat.mumble.permissions.sync_corporations') && $mainCharacter->affiliation->corporation_id) {
                $groups[] = "corp_{$mainCharacter->affiliation->corporation_id}";
            }

            // Add alliance group if enabled
            if (config('seat.mumble.permissions.sync_alliances') && $mainCharacter->affiliation->alliance_id) {
                $groups[] = "alliance_{$mainCharacter->affiliation->alliance_id}";
            }
        }

        return array_unique($groups);
    }

    /**
     * Check if user matches a mapping.
     */
    protected function userMatchesMapping(User $user, MumbleGroupMapping $mapping): bool
    {
        return match ($mapping->seat_type) {
            'role' => $user->hasRole($mapping->seat_identifier),
            'squad' => $user->squads->contains('id', $mapping->seat_identifier),
            'corporation' => $user->characters->contains(fn($char) => 
                $char->affiliation && $char->affiliation->corporation_id == $mapping->seat_identifier
            ),
            'alliance' => $user->characters->contains(fn($char) => 
                $char->affiliation && $char->affiliation->alliance_id == $mapping->seat_identifier
            ),
            default => false,
        };
    }
}
