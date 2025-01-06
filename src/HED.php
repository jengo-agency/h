<?php

namespace Jengo;

class HED {
    /**
     * Magic method for tag echo.
     */
    public static function __callStatic(string $tag, array $arguments): void {
        $ret = "<div style='border: 1px solid gray; display: grid;	width: 100%;height:auto;grid-template-columns: repeat(2, 1fr);margin-top:1em;'>";
        $ret .= '<div style="height:100%"><textarea readonly style="display:block;min-width: 0;width:100%;height:auto;min-height:200px;">' . H::__callStatic($tag,  $arguments) . '</textarea><span style="width:100%:">Mode: ' . H::getMode() . '</span></div>';
        $ret .= '<div style="min-width: 0;width:100%;">' . H::__callStatic($tag, $arguments) . '</div>';
        $ret .= '</div>';
        echo $ret;
    }
}
