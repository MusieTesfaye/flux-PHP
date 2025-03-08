<?php

declare(strict_types=1);

namespace Flux\Auth\Providers;

use Flux\Auth\User;
use Flux\Config\ConfigManager;
use Flux\Http\Request;
use Flux\Environment\EnvLoader;

class FirebaseProvider implements AuthProviderInterface
{
    /**
     * @var ConfigManager The configuration manager
     */
    private ConfigManager $config;
    
    /**
     * @var string The Firebase API key
     */
    private string $apiKey;
    
    /**
     * @var string The Firebase project ID
     */
    private string $projectId;
    
    /**
     * Create a new Firebase provider instance
     */
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        $this->apiKey = EnvLoader::get('FIREBASE_API_KEY', '');
        $this->projectId = EnvLoader::get('FIREBASE_PROJECT_ID', '');
    }
    
    /**
     * Attempt to authenticate a user with the given credentials
     */
    public function attempt(array $credentials): ?User
    {
        if (!isset($credentials['email']) || !isset($credentials['password'])) {
            return null;
        }
        
        // Firebase REST API endpoint for email/password sign-in
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$this->apiKey}";
        
        $data = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'returnSecureToken' => true,
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!$response || isset($response['error'])) {
            return null;
        }
        
        return $this->mapUserFromFirebase($response);
    }
    
    /**
     * Retrieve a user by their ID
     */
    public function retrieveById(string $id): ?User
    {
        // Firebase REST API endpoint for user lookup
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->apiKey}";
        
        $data = [
            'idToken' => $id,
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!$response || isset($response['error']) || empty($response['users'])) {
            return null;
        }
        
        return $this->mapUserFromFirebase($response['users'][0]);
    }
    
    /**
     * Validate a token
     */
    public function validateToken(string $token): ?User
    {
        // Firebase REST API endpoint for token verification
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->apiKey}";
        
        $data = [
            'idToken' => $token,
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!$response || isset($response['error']) || empty($response['users'])) {
            return null;
        }
        
        return $this->mapUserFromFirebase($response['users'][0]);
    }
    
    /**
     * Handle an OAuth callback
     */
    public function handleOAuthCallback(Request $request): ?User
    {
        // Firebase handles OAuth on the client side
        return null;
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function getOAuthUrl(): string
    {
        // Firebase handles OAuth on the client side
        return '';
    }
    
    /**
     * Make a request to the Firebase API
     */
    private function makeRequest(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Map a user from Firebase
     */
    private function mapUserFromFirebase(array $userData): User
    {
        $id = $userData['localId'] ?? $userData['uid'] ?? '';
        $email = $userData['email'] ?? '';
        $name = $userData['displayName'] ?? $email;
        
        // Extract additional attributes
        $attributes = [];
        
        if (isset($userData['photoUrl'])) {
            $attributes['avatar'] = $userData['photoUrl'];
        }
        
        if (isset($userData['emailVerified'])) {
            $attributes['email_verified'] = $userData['emailVerified'];
        }
        
        return new User(
            $id,
            $name,
            $email,
            'firebase',
            $attributes
        );
    }
    
    /**
     * Create a new user
     */
    public function createUser(string $name, string $email, string $password): ?User
    {
        // Firebase REST API endpoint for user creation
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$this->apiKey}";
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (!$response || isset($response['error'])) {
            return null;
        }
        
        // Update the user profile to set the display name
        $updateUrl = "https://identitytoolkit.googleapis.com/v1/accounts:update?key={$this->apiKey}";
        
        $updateData = [
            'idToken' => $response['idToken'],
            'displayName' => $name,
            'returnSecureToken' => true,
        ];
        
        $updateResponse = $this->makeRequest($updateUrl, $updateData);
        
        if (!$updateResponse || isset($updateResponse['error'])) {
            // Still return the user even if the profile update failed
            return $this->mapUserFromFirebase($response);
        }
        
        return $this->mapUserFromFirebase($updateResponse);
    }
}

