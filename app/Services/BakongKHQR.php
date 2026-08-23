<?php

namespace App\Services;

class BakongKHQR
{
    public const CURRENCY_USD = '840';
    public const CURRENCY_KHR = '116';

    /**
     * Unified Generate KHQR (Merchant or Individual).
     */
    public static function generate(array $info): array
    {
        $accountType = strtolower(trim((string) ($info['account_type'] ?? $info['accountType'] ?? 'individual')));
        if ($accountType === 'merchant' || $accountType === '30') {
            return self::generateMerchant($info);
        }
        return self::generateIndividual($info);
    }

    /**
     * Generate Individual Dynamic/Static KHQR (Tag 29).
     */
    public static function generateIndividual(array $info): array
    {
        $bakongAccountId = trim($info['bakong_account_id'] ?? $info['bakongAccountId'] ?? '');
        $merchantName = trim($info['merchant_name'] ?? $info['merchantName'] ?? 'Sopheareaksa Pheak');
        $merchantCity = trim($info['merchant_city'] ?? $info['merchantCity'] ?? 'Phnom Penh');
        $rawCurrency = strtoupper(trim((string) ($info['currency'] ?? 'USD')));
        $currencyCode = ($rawCurrency === 'KHR' || $rawCurrency === '116') ? self::CURRENCY_KHR : self::CURRENCY_USD;

        $amount = $info['amount'] ?? null;
        $billNumber = trim((string) ($info['bill_number'] ?? $info['billNumber'] ?? ''));
        $storeLabel = trim((string) ($info['store_label'] ?? $info['storeLabel'] ?? ''));
        $terminalLabel = trim((string) ($info['terminal_label'] ?? $info['terminalLabel'] ?? ''));
        $mobileNumber = trim((string) ($info['mobile_number'] ?? $info['mobileNumber'] ?? ''));
        $mcc = trim((string) ($info['mcc'] ?? '5999'));

        if (empty($bakongAccountId)) {
            return [
                'status' => ['code' => 1, 'message' => 'Bakong Account ID is required.'],
                'data' => null,
            ];
        }

        $payload = '';

        // Tag 00: Payload Format Indicator (Always 01)
        $payload .= self::formatTag('00', '01');

        // Tag 01: Point of Initiation Method (12 = Dynamic, 11 = Static)
        $hasAmount = ($amount !== null && (float) $amount > 0);
        $isDynamic = $hasAmount || !empty($billNumber);
        $payload .= self::formatTag('01', $isDynamic ? '12' : '11');

        // Tag 29: Merchant Account Information (Individual)
        $tag29Value = self::formatTag('00', $bakongAccountId);
        if (!empty($info['account_information'] ?? '')) {
            $tag29Value .= self::formatTag('01', $info['account_information']);
        }
        if (!empty($info['acquiring_bank'] ?? '')) {
            $tag29Value .= self::formatTag('02', $info['acquiring_bank']);
        }
        $payload .= self::formatTag('29', $tag29Value);

        // Tag 52: Merchant Category Code (5999)
        $payload .= self::formatTag('52', !empty($mcc) ? $mcc : '5999');

        // Tag 53: Transaction Currency (840 or 116)
        $payload .= self::formatTag('53', $currencyCode);

        // Tag 54: Transaction Amount
        if ($hasAmount) {
            if ($currencyCode === self::CURRENCY_KHR) {
                $formattedAmount = (string) round((float) $amount);
            } else {
                $formattedAmount = (fmod((float) $amount, 1.0) == 0.0)
                    ? (string) ((int) $amount)
                    : number_format((float) $amount, 2, '.', '');
            }
            $payload .= self::formatTag('54', $formattedAmount);
        }

        // Tag 58: Country Code (KH)
        $payload .= self::formatTag('58', 'KH');

        // Tag 59: Merchant/Account Name (Max 25 chars)
        $payload .= self::formatTag('59', mb_substr($merchantName, 0, 25));

        // Tag 60: Merchant City (Max 15 chars)
        $payload .= self::formatTag('60', mb_substr($merchantCity, 0, 15));

        // Tag 62: Additional Data Field Template
        $tag62Value = '';
        if (!empty($billNumber)) {
            $tag62Value .= self::formatTag('01', mb_substr($billNumber, 0, 25));
        }
        if (!empty($mobileNumber)) {
            $tag62Value .= self::formatTag('02', mb_substr($mobileNumber, 0, 25));
        }
        if (!empty($storeLabel)) {
            $tag62Value .= self::formatTag('03', mb_substr($storeLabel, 0, 25));
        }
        if (!empty($terminalLabel)) {
            $tag62Value .= self::formatTag('07', mb_substr($terminalLabel, 0, 25));
        }
        if (!empty($tag62Value)) {
            $payload .= self::formatTag('62', $tag62Value);
        }

        // Tag 99: Dynamic KHQR Timestamp (Required by NBC for dynamic QR)
        if ($isDynamic) {
            $nowMs = (int) round(microtime(true) * 1000);
            $expirationMs = $nowMs + (5 * 60 * 1000); // 5 minutes validity
            $tag99Value = self::formatTag('00', (string) $nowMs) . self::formatTag('01', (string) $expirationMs);
            $payload .= self::formatTag('99', $tag99Value);
        }

        // Tag 63: CRC16
        $crcPayload = $payload . '6304';
        $checksum = self::calculateCrc16($crcPayload);
        $finalQr = $crcPayload . $checksum;

        $md5 = md5($finalQr);

        return [
            'status' => ['code' => 0, 'message' => 'Success'],
            'data' => [
                'qr' => $finalQr,
                'md5' => $md5,
            ],
        ];
    }

    /**
     * Generate Merchant Dynamic/Static KHQR (Tag 30).
     */
    public static function generateMerchant(array $info): array
    {
        $bakongAccountId = trim($info['bakong_account_id'] ?? $info['bakongAccountId'] ?? '');
        $merchantId = trim($info['merchant_id'] ?? $info['merchantId'] ?? $bakongAccountId);
        $acquiringBank = trim($info['acquiring_bank'] ?? $info['acquiringBank'] ?? 'Bakong');
        $merchantName = trim($info['merchant_name'] ?? $info['merchantName'] ?? 'Sopheareaksa Pheak');
        $merchantCity = trim($info['merchant_city'] ?? $info['merchantCity'] ?? 'Phnom Penh');
        $rawCurrency = strtoupper(trim((string) ($info['currency'] ?? 'USD')));
        $currencyCode = ($rawCurrency === 'KHR' || $rawCurrency === '116') ? self::CURRENCY_KHR : self::CURRENCY_USD;

        $amount = $info['amount'] ?? null;
        $billNumber = trim((string) ($info['bill_number'] ?? $info['billNumber'] ?? ''));
        $storeLabel = trim((string) ($info['store_label'] ?? $info['storeLabel'] ?? ''));
        $terminalLabel = trim((string) ($info['terminal_label'] ?? $info['terminalLabel'] ?? ''));
        $mobileNumber = trim((string) ($info['mobile_number'] ?? $info['mobileNumber'] ?? ''));
        $mcc = trim((string) ($info['mcc'] ?? '5999'));

        if (empty($bakongAccountId)) {
            return [
                'status' => ['code' => 1, 'message' => 'Bakong Account ID is required.'],
                'data' => null,
            ];
        }

        $payload = '';

        // Tag 00: Payload Format Indicator
        $payload .= self::formatTag('00', '01');

        // Tag 01: Point of Initiation Method
        $hasAmount = ($amount !== null && (float) $amount > 0);
        $isDynamic = $hasAmount || !empty($billNumber);
        $payload .= self::formatTag('01', $isDynamic ? '12' : '11');

        // Tag 30: Merchant Account Information
        $tag30Value = self::formatTag('00', $bakongAccountId);
        if (!empty($merchantId)) {
            $tag30Value .= self::formatTag('01', $merchantId);
        }
        if (!empty($acquiringBank)) {
            $tag30Value .= self::formatTag('02', $acquiringBank);
        }
        $payload .= self::formatTag('30', $tag30Value);

        // Tag 52: Merchant Category Code (5999)
        $payload .= self::formatTag('52', !empty($mcc) ? $mcc : '5999');

        // Tag 53: Transaction Currency
        $payload .= self::formatTag('53', $currencyCode);

        // Tag 54: Transaction Amount
        if ($hasAmount) {
            if ($currencyCode === self::CURRENCY_KHR) {
                $formattedAmount = (string) round((float) $amount);
            } else {
                $formattedAmount = (fmod((float) $amount, 1.0) == 0.0)
                    ? (string) ((int) $amount)
                    : number_format((float) $amount, 2, '.', '');
            }
            $payload .= self::formatTag('54', $formattedAmount);
        }

        // Tag 58: Country Code
        $payload .= self::formatTag('58', 'KH');

        // Tag 59: Merchant Name
        $payload .= self::formatTag('59', mb_substr($merchantName, 0, 25));

        // Tag 60: Merchant City
        $payload .= self::formatTag('60', mb_substr($merchantCity, 0, 15));

        // Tag 62: Additional Data Field Template
        $tag62Value = '';
        if (!empty($billNumber)) {
            $tag62Value .= self::formatTag('01', mb_substr($billNumber, 0, 25));
        }
        if (!empty($mobileNumber)) {
            $tag62Value .= self::formatTag('02', mb_substr($mobileNumber, 0, 25));
        }
        if (!empty($storeLabel)) {
            $tag62Value .= self::formatTag('03', mb_substr($storeLabel, 0, 25));
        }
        if (!empty($terminalLabel)) {
            $tag62Value .= self::formatTag('07', mb_substr($terminalLabel, 0, 25));
        }
        if (!empty($tag62Value)) {
            $payload .= self::formatTag('62', $tag62Value);
        }

        // Tag 99: Dynamic KHQR Timestamp
        if ($isDynamic) {
            $nowMs = (int) round(microtime(true) * 1000);
            $expirationMs = $nowMs + (5 * 60 * 1000);
            $tag99Value = self::formatTag('00', (string) $nowMs) . self::formatTag('01', (string) $expirationMs);
            $payload .= self::formatTag('99', $tag99Value);
        }

        // Tag 63: CRC16
        $crcPayload = $payload . '6304';
        $checksum = self::calculateCrc16($crcPayload);
        $finalQr = $crcPayload . $checksum;

        $md5 = md5($finalQr);

        return [
            'status' => ['code' => 0, 'message' => 'Success'],
            'data' => [
                'qr' => $finalQr,
                'md5' => $md5,
            ],
        ];
    }

    /**
     * Verify and decode a KHQR string.
     */
    public static function verify(string $qrString): array
    {
        $qrString = trim($qrString);
        if (strlen($qrString) < 8) {
            return [
                'status' => ['code' => 1, 'message' => 'Invalid KHQR string length.'],
                'data' => null,
            ];
        }

        $crcIndex = strrpos($qrString, '6304');
        if ($crcIndex === false || $crcIndex !== (strlen($qrString) - 8)) {
            return [
                'status' => ['code' => 2, 'message' => 'Missing or misplaced CRC tag.'],
                'data' => null,
            ];
        }

        $expectedPayload = substr($qrString, 0, $crcIndex + 4);
        $receivedCrc = strtoupper(substr($qrString, -4));
        $calculatedCrc = self::calculateCrc16($expectedPayload);

        if ($receivedCrc !== $calculatedCrc) {
            return [
                'status' => ['code' => 3, 'message' => 'CRC verification failed.'],
                'data' => null,
            ];
        }

        $parsed = self::parseTlv($qrString);

        return [
            'status' => ['code' => 0, 'message' => 'Valid KHQR'],
            'data' => [
                'payloadFormat' => $parsed['00'] ?? null,
                'pointOfInitiation' => $parsed['01'] ?? null,
                'merchantInfo' => isset($parsed['30']) ? self::parseTlv($parsed['30']) : (isset($parsed['29']) ? self::parseTlv($parsed['29']) : null),
                'currency' => ($parsed['53'] ?? '') === self::CURRENCY_KHR ? 'KHR' : (($parsed['53'] ?? '') === self::CURRENCY_USD ? 'USD' : ($parsed['53'] ?? null)),
                'amount' => $parsed['54'] ?? null,
                'countryCode' => $parsed['58'] ?? null,
                'merchantName' => $parsed['59'] ?? null,
                'merchantCity' => $parsed['60'] ?? null,
                'additionalData' => isset($parsed['62']) ? self::parseTlv($parsed['62']) : null,
                'crc' => $receivedCrc,
                'md5' => md5($qrString),
            ],
        ];
    }

    /**
     * Parse TLV string into associative array of tag => value.
     */
    public static function parseTlv(string $raw): array
    {
        $result = [];
        $length = strlen($raw);
        $i = 0;

        while ($i < $length - 4) {
            $tag = substr($raw, $i, 2);
            $valLen = (int) substr($raw, $i + 2, 2);
            $i += 4;

            if ($valLen > 0 && ($i + $valLen) <= $length) {
                $val = substr($raw, $i, $valLen);
                $result[$tag] = $val;
                $i += $valLen;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Format a single Tag-Length-Value chunk.
     */
    public static function formatTag(string $tag, string $value): string
    {
        $len = strlen($value);
        return sprintf('%02s%02d%s', $tag, $len, $value);
    }

    /**
     * Calculate CRC16-CCITT (polynomial 0x1021, init 0xFFFF).
     */
    public static function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
