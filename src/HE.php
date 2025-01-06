<?php
namespace Jengo;

class HE {
    /**
     * Magic method for tag echo.
     */
    public static function __callStatic(string $tag, array $arguments): void {
        echo H::__callStatic($tag,  $arguments);
    }
}
