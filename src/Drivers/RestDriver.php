<?php

namespace Seat\MumbleConnector\Drivers;

use Seat\MumbleConnector\Contracts\MumbleDriverInterface;
use Seat\MumbleConnector\Models\MumbleUser;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * REST API Driver for Mumble Server
 * 
 * Requires a REST API wrapper like Murmur-REST running.
 * This is the easiest option as it uses standard HTTP calls.
 * 
 * @see https://github.com/alfg/murmur-rest
 */
class RestDriver implements MumbleDriverInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $serverId;
    protected int $timeout;

    public function __construct()
    {
        // Helper to get setting from DB
        $get = function($name, $default) {
            $row = \DB::table('global_settings')->where('name', $name)->first();
            return $row ? $row->value : $default;
        };

        $this->baseUrl = rtrim($get('mumble.rest_url', config('seat.mumble.rest.url', 'http://127.0.0.1:8080')), '/');
        $this->apiKey = $get('mumble.rest_api_key', config('seat.mumble.rest.api_key', ''));
        $this->serverId = (int) $get('mumble.rest_server_id', config('seat.mumble.rest.server_id', 1));
        $this->timeout = (int) $get('mumble.rest_timeout', config('seat.mumble.rest.timeout', 30));
    }

    /**
     * Make an HTTP request to the REST API.
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = sprintf('%s/api/v1/servers/%d%s', $this->baseUrl, $this->serverId, $endpoint);

        $request = Http::timeout($this->timeout)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        if ($this->apiKey) {
            $request->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        try {
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => throw new Exception("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                throw new Exception("API request failed: " . $response->body());
            }

            return $response->json() ?? [];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new Exception("HTTP request failed: " . $e->getMessage());
        }
    }

    public function testConnection(): array
    {
        try {
            // Try to get server stats
            $url = sprintf('%s/api/v1/servers/%d/stats', $this->baseUrl, $this->serverId);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json']);
            
            if ($this->apiKey) {
                $response->withHeaders(['X-API-Key' => $this->apiKey]);
            }
            
            $result = $response->get($url);
            
            if ($result->successful()) {
                $data = $result->json();
                // Use SeAT's own table for the accurate managed-user count
                $registeredCount = \DB::table('mumble_users')->where('is_active', true)->count();
                return [
                    'success' => true,
                    'message' => sprintf('Connected! Registered Users: %d', $registeredCount),
                    'driver'  => 'rest',
                    'data'    => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'REST API returned error: ' . $result->body(),
                'driver' => 'rest',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'REST API connection failed: ' . $e->getMessage(),
                'driver' => 'rest',
            ];
        }
    }

    public function getUsers(): array
    {
        try {
            return $this->request('GET', '/users');
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGroups(): array
    {
        try {
            return $this->request('GET', '/acl');
        } catch (Exception $e) {
            return [];
        }
    }

    public function syncUser(MumbleUser $user, array $groups): bool
    {
        try {
            // Use display name (formatted with ticker/tags) for Mumble server
            // Fall back to mumble_username if display name not set yet
            $mumbleName = $user->mumble_display_name ?: $user->mumble_username;

            $userData = [
                'name'         => $user->mumble_username,
                'display_name' => $user->mumble_display_name,
            ];

            if ($user->password_hash) {
                $userData['password'] = $user->password_hash;
            }

            // If we already have a mumble_user_id, just update directly
            if ($user->mumble_user_id) {
                $this->request('PUT', "/users/{$user->mumble_user_id}", $userData);
            } else {
                // Look up by display name to see if already registered
                $existingUsers = $this->request('GET', '/users', [
                    'filter' => $mumbleName,
                ]);

                if (!empty($existingUsers)) {
                    $userId = $existingUsers[0]['id'] ?? null;
                    if ($userId) {
                        $this->request('PUT', "/users/{$userId}", $userData);
                        $user->update(['mumble_user_id' => $userId]);
                    }
                } else {
                    $result = $this->request('POST', '/users', $userData);
                    if (isset($result['id'])) {
                        $user->update(['mumble_user_id' => $result['id']]);
                    }
                }
            }

            // Update group memberships
            foreach ($groups as $group) {
                $this->addUserToGroup($user, $group);
            }

            return true;
        } catch (Exception $e) {
            \Log::error('REST sync failed', [
                'user' => $user->mumble_username,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function removeUser(MumbleUser $user): bool
    {
        try {
            if ($user->mumble_user_id) {
                $this->request('DELETE', "/users/{$user->mumble_user_id}");
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }



    public function addUserToGroup(MumbleUser $user, string $group): bool
    {
        try {
            $this->request('POST', "/users/{$user->mumble_user_id}/groups", [
                'group' => $group,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeUserFromGroup(MumbleUser $user, string $group): bool
    {
        try {
            $this->request('DELETE', "/users/{$user->mumble_user_id}/groups/{$group}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getServerInfo(): array
    {
        try {
            return $this->request('GET', '/stats');
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
