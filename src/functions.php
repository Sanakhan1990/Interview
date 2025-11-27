<?php
class MissingRequiredFieldException extends Exception {}
class UnsupportedFormatException extends Exception {}
use App\FileReader;


// ====== Main function Starts Here================================ 
function Main(): int
{
    $options = getopt('', ['file:', 'unique-combinations:']);

    if (!isset($options['file']) || !isset($options['unique-combinations'])) {
        cli_print_error();
        return 1;
    }

    $inputFile  = $options['file'];
    $outputFile = $options['unique-combinations'];

    if (!is_readable($inputFile)) {
        fwrite(STDERR, "Error: Cannot read input file: $inputFile\n");
        return 1;
    }

    // Create generator for rows based on file format
    try {
        $rowGenerator = FileReader::getRowGenerator($inputFile);
    } catch (UnsupportedFormatException $e) {
        fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
        return 1;
    }

    $aggregatedCounts = [];  
    $rowIndex         = 0;

    try {
        foreach ($rowGenerator as $row) {
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

            // Skip fully empty rows
            $nonEmpty = false;
            foreach ($row as $v) {
                if ($v !== null && $v !== '') {
                    $nonEmpty = true;
                    break;
                }
            }
            if (!$nonEmpty) {
                continue;
            }

            // validation for required headers
            if ($rowIndex === 0) {
                validate_required_headers(array_keys($row));
            }

            // Map to canonical Product array
            $product = map_row_to_product($row);

            // Print product representation to stdout as JSON
            echo json_encode($product, JSON_UNESCAPED_UNICODE) . PHP_EOL;

            // Aggregate combo counts
            add_to_aggregated_counts($aggregatedCounts, $product);

            $rowIndex++;
        }
    } catch (MissingRequiredFieldException $e) {
        fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
        return 1;
    } catch (Exception $e) {
        fwrite(STDERR, "Unexpected error: " . $e->getMessage() . PHP_EOL);
        return 1;
    }

    // Write unique combinations CSV with count
    if (($fh = fopen($outputFile, 'w')) === false) {
        fwrite(STDERR, "Error: Cannot write to output file: $outputFile\n");
        return 1;
    }

    // Header row
    fputcsv($fh, ['make', 'model', 'colour', 'capacity', 'network', 'grade', 'condition', 'count']);

    // Data rows
    foreach ($aggregatedCounts as $key => $count) {
        $parts = explode('|', $key);
        while (count($parts) < 7) {
            $parts[] = '';
        }
        list($make, $model, $colour, $capacity, $network, $grade, $condition) = $parts;

        fputcsv($fh, [
            $make,
            $model,
            $colour !== '' ? $colour : null,
            $capacity !== '' ? $capacity : null,
            $network !== '' ? $network : null,
            $grade !== '' ? $grade : null,
            $condition !== '' ? $condition : null,
            $count,
        ]);
    }

    fclose($fh);

    echo "Unique combinations written to: $outputFile" . PHP_EOL;
    return 0;
}

function cli_print_error(): void
{
    fwrite(STDERR, "Missing Parameters:\n");
    fwrite(STDERR, "  php parser.php --file=INPUT_FILE --unique-combinations=OUTPUT_FILE\n\n");
}

// ============================================================
//  Validation & mapping to Product array
// ============================================================

function validate_required_headers(array $headerKeys): void
{
    global $FIELD_ALIASES, $REQUIRED_FIELDS;
    $lowerHeader = [];

    //echo "DEBUG REQUIRED: ";
    //var_dump($REQUIRED_FIELDS);

    //echo "DEBUG ALIASES: ";
    //var_dump($FIELD_ALIASES);

    foreach ($headerKeys as $key) {
        $lowerHeader[] = strtolower(trim((string)$key));
    }
    var_dump($lowerHeader);
    foreach ($REQUIRED_FIELDS as $field) {
        $aliases = isset($FIELD_ALIASES[$field]) ? $FIELD_ALIASES[$field] : [];
        $found   = false;

        foreach ($aliases as $alias) {
            if (in_array($alias, $lowerHeader, true)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new MissingRequiredFieldException(
                'Required field "' . $field . '" not found in file headings.'
            );
        }
    }
}

function map_row_to_product(array $row): array
{

    // Normalize keys to lowercase
    $normalized = [];
    foreach ($row as $key => $value) {
        $normalized[strtolower(trim((string)$key))] = $value;
    }

    $get = function (string $canonicalField, bool $required = false) use ($normalized) {
        global $FIELD_ALIASES;
        if (!isset($FIELD_ALIASES[$canonicalField])) {
            if ($required) {
                throw new MissingRequiredFieldException(
                    'Required field "' . $canonicalField . '" does not have aliases configured.'
                );
            }
            return null;
        }

        foreach ($FIELD_ALIASES[$canonicalField] as $alias) {
            if (array_key_exists($alias, $normalized)) {
                $value = $normalized[$alias];
                if ($value !== null && $value !== '') {
                    return trim((string)$value);
                }
            }
        }

        if ($required) {
            throw new MissingRequiredFieldException(
                'Required field "' . $canonicalField . '" not found in row.'
            );
        }

        return null;
    };

    $product = [];
    $product['make']      = $get('make', true);
    $product['model']     = $get('model', true);
    $product['colour']    = $get('colour', false);
    $product['capacity']  = $get('capacity', false);
    $product['network']   = $get('network', false);
    $product['grade']     = $get('grade', false);
    $product['condition'] = $get('condition', false);

    return $product;
}

// ============================================================
//  Unique combination aggregation
// ============================================================

function create_product_key(array $product): string
{
    $parts = [
        $product['make'],
        $product['model'],
        $product['colour']    ?? '',
        $product['capacity']  ?? '',
        $product['network']   ?? '',
        $product['grade']     ?? '',
        $product['condition'] ?? '',
    ];
    return implode('|', $parts);
}

function add_to_aggregated_counts(array &$counts, array $product): void
{
    $key = create_product_key($product);
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key]++;
}
