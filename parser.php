#!/usr/bin/env php
<?php
/**
 * Supplier Product List Processor (CLI)
 *
 * Usage:
 *   php parser.php --file=INPUT_FILE --unique-combinations=OUTPUT_FILE
 */

require __DIR__ . '/vendor/autoload.php'; // Composer autoloader loads src/functions.php
require_once __DIR__ . '/config/config.php';

// Delegate to a main() function defined in src/functions.php
exit(main_cli($argv));
