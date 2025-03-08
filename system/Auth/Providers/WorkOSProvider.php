<?php

declare(strict_types=1);

namespace Flux\Auth\Providers;

use Flux\Auth\User;
use Flux\Config\ConfigManager;
use Flux\Http\Request;
use Flux\Environment\EnvLoader;

class WorkOSProvider implements AuthProviderInterface
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var string The WorkOS API key
     */
    private string $apiKey;
    
    /**
     * @var string The WorkOS client ID
     */
    private string $clientId;
    
    /**
     * @var string The redirect URI
     */
    private string $redirectUri;
    
    /**
     * Create a new WorkOS provider instance
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        $this->apiKey = EnvLoader::get('WORKOS_API_KEY', '');
        $this->clientId = EnvLoader::get('WORKOS_CLIENT_ID', '');
        $this->redirectUri = EnvLoader::get('WORKOS_REDIRECT_URI', '');
    }
    
    /**
     * Attempt to authenticate a user with the given credentials
     */
    public function attempt(array $credentials): ?User
    {
        // WorkOS doesn't support username/password authentication
        return null;
    }
    
    /**
     * Retrieve a user by their ID
     */
    public function retrieveById(string $id): ?User
    {
        // WorkOS API endpoint for user profile
        $url = "https://api.workos.com/user/{$id}";
        
        $response = $this->makeRequest($url);
        
        if (!$response || isset($response['error'])) {
            return null;
        }
        
        return $this->mapUserFromWorkOS($response);
    }
    
    /**
     * Validate a token
     */
    public function validateToken(string $token): ?User
    {
        // WorkOS API endpoint for token validation
        $url = "https://api.workos.com/sso/token";
        
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->apiKey,
            'grant_type' => 'authorization_code',
            'code' => $token,
        ];
        
        $response = $this->makeRequest($url, $data, 'POST');
        
        if (!$response || isset($response['error'])) {
            return null;
        }
        
        // Get the user profile
        $profileUrl = "https://api.workos.com/sso/profile";
        
        $headers = [
            'Authorization: Bearer ' . $response['access_token'],
        ];
        
        $profileResponse = $this->makeRequest($profileUrl, [], 'GET', $headers);
        
        if (!$profileResponse || isset($profileResponse['error'])) {
            return null;
        }
        
        return $this->mapUserFromWorkOS($profileResponse);
    }
    
    /**
     * Handle an OAuth callback
     */
    public function handleOAuthCallback(Request $request): ?User
    {
        $code = $request->get('code');
        
        if (!$code) {
            return null;
        }
        
        return $this->validateToken($code);
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function getOAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
        ];
        
        return 'https://api.workos.com/sso/authorize?' . http_build_query($params);
    }
    
    /**
     * Make a request to the WorkOS API
     */
    private function makeRequest(string $url, array $data = [], string $method = 'GET', array $headers = []): ?array
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set the authorization header if not provided
        if (!$this->hasHeader($headers, 'Authorization')) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        
        // Set content type header if not provided
        if (!$this->hasHeader($headers, 'Content-Type')) {
            $headers[] = 'Content-Type: application/json';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif (!empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Check if a header exists in the headers array
     */
    private function hasHeader(array $headers, string $name): bool
    {
        $name = strtolower($name);
        
        foreach ($headers as $header) {
            if (strtolower(substr($header, 0, strlen($name))) === $name) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Map a user from WorkOS
     */
    private function mapUserFromWorkOS(array $userData): User
    {
        $id = $userData['id'] ?? '';
        $email = $userData['email'] ?? '';
        $name = $userData['first_name'] ?? '';
        
        if (isset($userData['last_name'])) {
            $name .= ' ' . $userData['last_name'];
        }
        
        if (empty($name)) {
            $name = $email;
        }
        
        // Extract additional attributes
        $attributes = [];
        
        if (isset($userData['raw_attributes'])) {
            $attributes = $userData['raw_attributes'];
        }
        
        return new User(
            $id,
            $name,
            $email,
            'workos',
            $attributes
        );
    }
}

