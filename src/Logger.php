<?php

class Logger {
    public static function yell(string $message) {
        echo $message;
    }

    public static function checkPortUsage(int $port) {
        $cmd = sprintf('lsof -i:%d -t', $port);
        $output = shell_exec($cmd);
        if (!$output) return;

        $pids = explode("\n", trim($output));
        foreach ($pids as $pid) {
            if (!is_numeric($pid)) continue;

            self::yell("Killing process $pid using port $port\n");
            posix_kill((int)$pid, SIGTERM);
        }

        sleep(1);
    }
}
