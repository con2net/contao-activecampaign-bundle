<?php
// File: vendor/con2net/contao-activecampaign-bundle/src/Service/TokenGenerator.php

declare(strict_types=1);

namespace Con2net\ContaoActiveCampaignBundle\Service;

/**
 * Generator für sichere Transfer-Tokens
 * Erzeugt kryptographisch sichere, URL-sichere Tokens
 */
class TokenGenerator
{
    /**
     * Generiert einen sicheren, URL-freundlichen Token
     *
     * @param int $length Länge des Tokens (default: 32)
     * @return string Der generierte Token
     */
    public function generate(int $length = 32): string
    {
        // Kryptographisch sicher
        $bytes = random_bytes($length);

        // URL-sicher kodieren (Base64, aber URL-freundlich)
        $token = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        // Auf gewünschte Länge kürzen
        return substr($token, 0, $length);
    }

    /**
     * Generiert einen längeren Token für höhere Sicherheit
     *
     * @return string Token mit 64 Zeichen
     */
    public function generateLong(): string
    {
        return $this->generate(64);
    }

    /**
     * Validiert Token-Format
     *
     * @param string $token Zu validierender Token
     * @return bool Token hat gültiges Format
     */
    public function isValidFormat(string $token): bool
    {
        // Mindestens 16 Zeichen, nur alphanumerisch + - und _
        return strlen($token) >= 16
            && preg_match('/^[a-zA-Z0-9_-]+$/', $token) === 1;
    }
}
