<?php
// src/Config/MailtrapConfig.php  (or wherever you keep it)

class MailtrapConfig
{
    // ---- SMTP ----
    private ?string $username;
    private ?string $password;
    private string $host;
    private int $port;

    // ---- API ----
    private string $apiKey;
    private string $apiBaseUrl = 'https://send.api.mailtrap.io/api/send';

    public function __construct()
    {
        // SMTP (existing)
        $this->username = $_ENV['MAILTRAP_USERNAME'] ?? null;
        $this->password = $_ENV['MAILTRAP_PASSWORD'] ?? null;
        $this->host     = $_ENV['MAILTRAP_HOST'] ?? 'smtp.mailtrap.io';
        $this->port     = (int)($_ENV['MAILTRAP_PORT'] ?? 2525);

        if (!$this->username || !$this->password) {
            throw new Exception(
                "Mailtrap SMTP credentials missing. Set MAILTRAP_USERNAME and MAILTRAP_PASSWORD in .env"
            );
        }

        // API
        $this->apiKey = $_ENV['MAILTRAP_API_KEY'] ?? '';
        if (!$this->apiKey) {
            throw new Exception(
                "Mailtrap API key missing. Set MAILTRAP_API_KEY in .env"
            );
        }
    }

    // ---- SMTP Getters (unchanged) ----
    public function getUsername(): string { return $this->username; }
    public function getPassword(): string { return $this->password; }
    public function getHost(): string     { return $this->host; }
    public function getPort(): int        { return $this->port; }

    // ---- API Getter ----
    public function getApiKey(): string { return $this->apiKey; }

    // ---- SEND VIA MAILTRAP API (REAL DELIVERY) ----
    public function sendViaApi(array $payload): array
    {
        $ch = curl_init($this->apiBaseUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        $data = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $data['errors'][0] ?? 'Unknown error';
            throw new Exception("Mailtrap API error ($httpCode): $msg");
        }

        return $data; // contains message_ids
    }
}

