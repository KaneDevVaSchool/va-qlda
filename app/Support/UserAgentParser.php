<?php

namespace App\Support;

class UserAgentParser
{
    /**
     * @return array{browser: string, os: string, device_name: string, label: string}
     */
    public static function parse(?string $userAgent): array
    {
        $ua = $userAgent ?? '';

        $os = 'Unknown';
        if (preg_match('/Windows NT/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        $browser = 'Unknown';
        if (preg_match('/Edg\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\//i', $ua) && ! preg_match('/Edg/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//i', $ua) && ! preg_match('/Chrome/i', $ua)) {
            $browser = 'Safari';
        }

        $deviceName = 'Desktop';
        if ($os === 'iOS') {
            $deviceName = 'iPhone / iPad';
        } elseif ($os === 'Android') {
            $deviceName = 'Android / Tablet';
        }

        $label = trim($browser.' · '.$os);

        return [
            'browser' => $browser,
            'os' => $os,
            'device_name' => $deviceName,
            'label' => $label,
        ];
    }
}
