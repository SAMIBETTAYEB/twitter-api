<?php

namespace samibettayeb\TwitterApi;

use DG\Twitter\Twitter;
use Illuminate\Support\Facades\Cache;

class TwitterApi
{
    public const GET = 'GET';
    public const POST = 'POST';

    private $appKey;
    private $appSecret;

    public function __construct($appKey, $appSecret)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
    }

    public function call($method = 'GET', $url = '', $headers = [], $postFields = [], $basic = false, $username = '', $password = '')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $authHeader = [];
        if (!$basic) {
            if ($basic != -4) {
                if (config('twitter-api.TWITTER_OAUTH_VERSION') == 2) {
                    $authHeader = [
                        'Authorization: ' . Cache::get('token'),
                    ];
                } else {
                    $authHeader = [
                        Cache::get('Authorization'),
                    ];
                    $data = [
                        'oauth_consumer_key' => config('twitter-api.TWITTER_APP_KEY'),
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_token=' => Cache::get('access_token'),
                        'oauth_timestamp' => time(),
                        'oauth_nonce' => rand(11111111, 999999999),
                        'oauth_version' => '1.0',
                    ];
                    ksort($data);
                    $data['oauth_signature'] = $this->generateSignature($method, $url, $data);
                    $httpHeaders = [];
                    foreach ($data as $key => $value) {
                        $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
                    }

                    $authHeader = ['Authorization: OAuth ' . implode(', ', $httpHeaders)];
                }
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $h = array_merge($authHeader, $headers));
        if ($method == self::POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }
        if ($basic) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        if ($basic == -4) {
            parse_str($body, $res);
            $body = json_encode($res);
        }
        curl_close($ch);

        return json_decode(
            json_encode([
                'header' => $this->get_headers_from_curl_response($header),
                'body' => json_decode($body),
            ])
        );
    }

    public function getAccessToken($key, $secret)
    {
        $authResponse = $this->call(
            self::POST,
            'https://api.twitter.com/oauth2/token',
            [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ],
            [
                'grant_type' => 'client_credentials',
            ],
            true,
            $key,
            $secret
        );
        if ($authResponse->header->status_code == 200) {
            Cache::forever('token', $authResponse->body->token_type . ' ' . $authResponse->body->access_token);
        }

        return $authResponse;
    }

    public function authenticate()
    {
        $authResponse = $this->call(
            self::POST,
            'https://api.twitter.com/oauth2/token',
            [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ],
            [
                'grant_type' => 'client_credentials',
            ],
            true,
            config('twitter-api.TWITTER_APP_KEY'),
            config('twitter-api.TWITTER_APP_SECRET')
        );
        if ($authResponse->header->status_code == 200) {
            Cache::forever('token', $authResponse->body->token_type . ' ' . $authResponse->body->access_token);
        }
    }

    public function getFavorites($username, $count = 200)
    {
        $favoritesResponse = $this->call(
            self::GET,
            "https://api.twitter.com/1.1/favorites/list.json?count=$count&screen_name=$username",
            [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            ]
        );
        if ($favoritesResponse->header->status_code == 200) {
            //
        }

        return $favoritesResponse;
    }

    public function generateSignature($method = 'GET', $url = 'https://api.twitter.com/oauth/request_token', $data = [], $secret = '')
    {
        $signData = $method . '&' . urlencode($url) . '&' . urlencode(http_build_query($data));
        $signKey = urlencode(config('twitter-api.TWITTER_APP_SECRET')) . '&' . urlencode($secret);

        return base64_encode(hash_hmac('sha1', $signData, $signKey, true));
    }

    public function logUser()
    {
        $data = [
            'oauth_callback' => 'http://127.0.0.1:8000',
            'oauth_consumer_key' => config('twitter-api.TWITTER_APP_KEY'),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => rand(11111111, 999999999),
            'oauth_version' => '1.0',
        ];
        ksort($data);
        $signData = 'POST&' . urlencode('https://api.twitter.com/oauth/request_token') . '&' . urlencode(http_build_query($data));
        $secret = '';
        $signKey = urlencode(config('twitter-api.TWITTER_APP_SECRET')) . '&' . urlencode($secret);
        $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));
        $httpHeaders = [];
        foreach ($data as $key => $value) {
            $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
        }

        // Add OAuth header with all data
        $httpHeaders = 'Authorization: OAuth ' . implode(', ', $httpHeaders);

        return $this->call('POST', 'https://api.twitter.com/oauth/request_token', [
            $httpHeaders,
        ], [], -4);
    }

    public function requestAccessToken($oauth_token, $oauth_verifier)
    {
        $data = [
            'oauth_consumer_key' => config('twitter-api.TWITTER_APP_KEY'),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => rand(11111111, 999999999),
            'oauth_token' => $oauth_token,
            'oauth_version' => '1.0',
        ];
        ksort($data);
        $signData = 'POST&' . urlencode('https://api.twitter.com/oauth/access_token') . '&' . urlencode(http_build_query($data));
        $secret = '';
        $signKey = urlencode(config('twitter-api.TWITTER_APP_SECRET')) . '&' . urlencode($secret);
        $data['oauth_signature'] = base64_encode(hash_hmac('sha1', $signData, $signKey, true));
        $httpHeaders = [];
        foreach ($data as $key => $value) {
            $httpHeaders[] = urlencode($key) . '="' . urlencode($value) . '"';
        }

        // Add OAuth header with all data
        $httpHeaders = 'Authorization: OAuth ' . implode(', ', $httpHeaders);

        return $this->call('POST', 'https://api.twitter.com/oauth/access_token', [
            $httpHeaders,
        ], [
            'oauth_verifier' => $oauth_verifier,
        ], -4);
    }

    public function getTokenAndSecret()
    {
        return $this->logUser()->body;
    }

    private function get_headers_from_curl_response($response)
    {
        $headers = [];

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
                // $status = explode(' ', trim($line));
                // $statusCode = count($status) > 0 ? (int) $status[count($status) - 1] : 0;
                // $headers['status'] = $statusCode;
            } else {
                [$key, $value] = explode(': ', $line);

                $headers[$key] = $value;
            }
        }
        if (!in_array('status', array_keys($headers))) {
            $http_code_segments = explode(' ', trim($headers['http_code']));
            $headers['status'] = $http_code_segments[count($http_code_segments) - 1];
        }
        $headers['status_code'] = (int) $headers['status'];

        return $headers;
    }

    public function getUserInfo($accessToken, $accessTokenSecret)
    {
        $twitter = new Twitter($this->appKey, $this->appSecret, $accessToken, $accessTokenSecret);
        $userInfo = $twitter->cachedRequest('account/verify_credentials', ['include_email' => 'true']);
        if (!empty($userInfo->id)) {
            die('Invalid name or password');
        }

        return $userInfo;
    }

    public function getUserFavorites($accessToken, $accessTokenSecret, $username, $max_id = '')
    {
        $twitter = new Twitter($this->appKey, $this->appSecret, $accessToken, $accessTokenSecret);
        $data = ['count' => 200, 'screen_name' => $username];
        if ($max_id != '') {
            $data['max_id'] = $max_id;
        }
        $favorites = $twitter->cachedRequest('favorites/list.json', $data);
        if (count($favorites) > 1) {
            sleep(12);
            $favorites = array_merge($favorites, $this->getUserFavorites($accessToken, $accessTokenSecret, $username, $favorites[count($favorites) - 1]->id - 1));
        } elseif (count($favorites) == 1) {
            $favorites = array_merge($favorites, $this->getUserFavorites($accessToken, $accessTokenSecret, $username, $favorites[count($favorites) - 1]->id - 1));
        }

        return $favorites;
    }
}
