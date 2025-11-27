<?php
/**
 * Supplier Product List Processor
 *
 * Usage:
 *   php parser.php --file=INPUT_FILE --unique-combinations=OUTPUT_FILE
 */

require __DIR__ . '/vendor/autoload.php'; 
require_once __DIR__ . '/config/config.php';

exit(Main());
