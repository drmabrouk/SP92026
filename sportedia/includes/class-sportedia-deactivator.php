<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sportedia_Deactivator {
    public static function deactivate() {
        // Optional deactivation cleanup tasks
    }
}

class SM_Deactivator extends Sportedia_Deactivator {}
