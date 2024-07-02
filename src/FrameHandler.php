<?php

class FrameHandler {

    private const DEFAULT_OFFSET = 7;
    private const NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH   = 126;
    private const NEXT_EIGHT_BYTES_IS_PAYLOAD_LENGTH = 127;
    private const UNPACK_FORMAT_ARG_UNSIGNED_CHARS_ENTIRE_STRING = 'C*';
    private const PACK_FORMAT_ARG_UNSIGNED_SHORT_BIG_ENDIAN = 'n';
    private const PACK_FORMAT_ARG_UNSIGNED_LONG_LONG = 'J';

    /**
     * Credits:
     * https://www.openmymind.net/WebSocket-Framing-Masking-Fragmentation-and-More/
     */

    public function decodeFrame($data) {
        $bytes = unpack(self::UNPACK_FORMAT_ARG_UNSIGNED_CHARS_ENTIRE_STRING, $data);

        if (empty($bytes)) return;

        $bytes = array_values($bytes);

        // $fin = ($bytes[0] >> 7) & 1;
        // $opcode = $bytes[0] & 0x0F;

        $masked = ($bytes[1] >> 7) & 1;
        $payloadLength = $bytes[1] & 127;

        $scalars = $this->shiftScalars($payloadLength, $bytes);

        $payload = array_slice($bytes, $scalars['payloadOffset'], $payloadLength);

        if ($masked)
            for ($i = 0; $i < $payloadLength; $i++)
                if (isset($payload[$i]))
                    $payload[$i] ^= $scalars['mask'][$i % 4];

        $decodedData = implode('', array_map('chr', $payload));

        return $decodedData;
    }

    private function shiftScalars(int $payloadLength, array $bytes): array {
        switch ($payloadLength) {
            case self::NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH:
                $payloadLength = ($bytes[2] << 8) | $bytes[3];
                $mask = array_slice($bytes, 4, 4);
                $payloadOffset = 8; 
                break;
            case self::NEXT_EIGHT_BYTES_IS_PAYLOAD_LENGTH:
                $payloadLength = ($bytes[2] << 56) | ($bytes[3] << 48) | ($bytes[4] << 40) | ($bytes[5] << 32) | ($bytes[6] << 24) | ($bytes[7] << 16) | ($bytes[8] << 8) | $bytes[9];
                $mask = array_slice($bytes, 10, 4);
                $payloadOffset = 14;
            default:
                $mask = array_slice($bytes, 2, 4);
                $payloadOffset = 6;
                break;
        }

        return compact('payloadOffset', 'mask');
    }

    public function decodeFrameDebug($data) {
        $bytes = unpack('C*', $data);

        $bytes = array_values($bytes);

        echo "Raw Bytes: " . implode(", ", $bytes) . "\n";

        $fin = ($bytes[0] >> 7) & 1;
        $opcode = $bytes[0] & 0x0F;
        $masked = ($bytes[1] >> 7) & 1;
        $payloadLength = $bytes[1] & 127;

        $payloadOffset = 2;

        if ($payloadLength === self::NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH) {
            $payloadLength = ($bytes[2] << 8) | $bytes[3];
            $mask = array_slice($bytes, 4, 4);
            $payloadOffset = 8;
        } elseif ($payloadLength === self::NEXT_EIGHT_BYTES_IS_PAYLOAD_LENGTH) {
            $payloadLength = ($bytes[2] << 56) | ($bytes[3] << 48) | ($bytes[4] << 40) | ($bytes[5] << 32) | ($bytes[6] << 24) | ($bytes[7] << 16) | ($bytes[8] << 8) | $bytes[9];
            $mask = array_slice($bytes, 10, 4);
            $payloadOffset = 14;
        } else {
            $mask = array_slice($bytes, 2, 4);
            $payloadOffset = 6;
        }

        echo "FIN: " . $fin . "\n";
        echo "Opcode: " . $opcode . "\n";
        echo "Masked: " . $masked . "\n";
        echo "Payload Length: " . $payloadLength . "\n";
        echo "Payload Offset: " . $payloadOffset . "\n";
        echo "Mask: " . implode(", ", $mask) . "\n";

        $payload = array_slice($bytes, $payloadOffset, $payloadLength);

        echo "Extracted Payload Length: " . count($payload) . "\n";
        echo "Extracted Payload: " . implode(", ", $payload) . "\n";

        if ($masked) {
            for ($i = 0; $i < $payloadLength; $i++) {
                if (isset($payload[$i])) {
                    $payload[$i] ^= $mask[$i % 4];
                }
            }
        }

        echo "Unmasked Payload Bytes: " . implode(", ", $payload) . "\n";

        $decodedData = implode('', array_map('chr', $payload));

        echo "Decoded Data: " . $decodedData . "\n";

        return $decodedData;
    }

    /**
     * For sending what we expect to receive
     */

     public static function encodeWebSocketFrame($message) {
        $length = strlen($message);
        $frame = chr(129);

        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0x80 | self::NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH) . pack(self::PACK_FORMAT_ARG_UNSIGNED_SHORT_BIG_ENDIAN, $length);
        } else {
            $frame .= chr(0x80 | self::NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH) . pack(self::PACK_FORMAT_ARG_UNSIGNED_LONG_LONG, 0, $length);
        }

        $mask = [];

        for ($i = 0; $i < 4; $i++)
            $mask[] = mt_rand(0, 255);

        $frame .= implode(array_map('chr', $mask));

        for ($i = 0; $i < $length; $i++)
            $frame .= chr(ord($message[$i]) ^ $mask[$i % 4]);

        return $frame;
    }

}