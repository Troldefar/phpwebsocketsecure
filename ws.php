<?php

/**
 * AUTOLOAD OR INCLUDE
 * Whatever floats your boat
 */

require_once './ws/src/Websocket.php';

/**
 * Get your configs as you wish
 */

function app(): object {
    return (object)[];
}

(new Websocket());