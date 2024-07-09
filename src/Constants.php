<?php

class Constants {

    public const DEFAULT_OFFSET = 7;
    public const NEXT_TWO_BYTES_IS_PAYLOAD_LENGTH   = 126;
    public const NEXT_EIGHT_BYTES_IS_PAYLOAD_LENGTH = 127;
    public const FINAL_TEXT_FRAME = 129;

    public const UNPACK_FORMAT_ARG_UNSIGNED_CHARS_ENTIRE_STRING = 'C*';

    public const PACK_FORMAT_ARG_HEX_ENTIRE_STRING = 'H*';
    public const PACK_FORMAT_ARG_UNSIGNED_SHORT_BIG_ENDIAN = 'n';
    public const PACK_FORMAT_ARG_UNSIGNED_LONG_LONG = 'J';

    public const CLIENT_CONNECTED = 'Client connected' . PHP_EOL;
    public const CLIENT_DISCONNECTED = 'Client disconnected' . PHP_EOL;
    public const MSG_RECEIVED_FROM_CLIENT = 'Received from client: ';

    public const ACK_RESPONSE = '#Sec-WebSocket-Accept:\s(.*)$#mUsi';
    public const WEBSOCKET_HEADER_KEY = '#Sec-WebSocket-Key: (.*)\r\n#';
    public const WEBSOCKET_HEADER_KEY_NOT_FOUND = 'WebSocket key not found in the request' . PHP_EOL;

    public const INVALID_HANDSHAKE_RESPONSE = 'Invalid WebSocket handshake response';

    public const DEFAULT_CLIENT_MESSAGE = '123456';

    public static function getConfigs(): object {
        return app()?->getConfig()?->get('integrations')?->websocket;
    }

}