<?php

namespace Reviu;

class Client
{
    private $baseUrl;
    private $token;
    private $timeout;
    private $caBundle;

    public function __construct($token, $baseUrl = 'https://reviu.online', $timeout = 10, $caBundle = null)
    {
        $baseUrl = rtrim((string) $baseUrl, '/');
        if (strpos($baseUrl, 'https://') !== 0) {
            throw new \InvalidArgumentException('Reviu API requires an HTTPS base URL.');
        }
        if (!preg_match('/^rv_live_[A-Za-z0-9_-]{32,}$/', (string) $token)) {
            throw new \InvalidArgumentException('Invalid Reviu API token format.');
        }
        if ((int) $timeout < 1 || (int) $timeout > 120) {
            throw new \InvalidArgumentException('Timeout must be between 1 and 120 seconds.');
        }
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The PHP cURL extension is required.');
        }

        $this->baseUrl = $baseUrl;
        $this->token = (string) $token;
        $this->timeout = (int) $timeout;
        $this->caBundle = $caBundle === null ? null : (string) $caBundle;
    }

    public function scheduleReview(array $customer, $idempotencyKey, \DateTimeInterface $scheduledAt = null)
    {
        $idempotencyKey = (string) $idempotencyKey;
        if (!preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $idempotencyKey)) {
            throw new \InvalidArgumentException('Idempotency key must contain 8-128 safe characters.');
        }

        $payload = array('customer' => $customer);
        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt->format(\DateTime::ATOM);
        }

        return $this->request(
            'POST',
            '/api/v1/review-requests.php',
            $payload,
            array('Idempotency-Key: ' . $idempotencyKey)
        );
    }

    public function getReviewRequest($jobId)
    {
        $jobId = (int) $jobId;
        if ($jobId < 1) throw new \InvalidArgumentException('Job ID must be positive.');
        return $this->request('GET', '/api/v1/review-request-status.php?id=' . $jobId);
    }

    private function request($method, $path, array $payload = null, array $extraHeaders = array())
    {
        $curl = curl_init($this->baseUrl . $path);
        $headers = array_merge(array(
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token,
        ), $extraHeaders);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        }
        if ($this->caBundle !== null) curl_setopt($curl, CURLOPT_CAINFO, $this->caBundle);

        if ($payload !== null) {
            $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
            if ($encoded === false) throw new \InvalidArgumentException('Payload cannot be encoded as JSON.');
            $headers[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $message = curl_error($curl);
            curl_close($curl);
            throw new ApiException('Reviu API connection failed: ' . $message);
        }
        curl_close($curl);

        $response = json_decode($body, true);
        if (!is_array($response)) {
            throw new ApiException('Reviu API returned an invalid response.', $status);
        }
        if ($status < 200 || $status >= 300) {
            $error = isset($response['error']) && is_array($response['error']) ? $response['error'] : array();
            throw new ApiException(
                isset($error['message']) ? $error['message'] : 'Reviu API request failed.',
                $status,
                isset($error['code']) ? $error['code'] : '',
                isset($error['details']) && is_array($error['details']) ? $error['details'] : array()
            );
        }
        return $response;
    }
}
