<?php

namespace App;

use UnsupportedFormatException;
use RuntimeException;

class FileReader
{
    /**
     * Decide to check which reader function to use based on the file extension.
     * Returns a generator of associative rows.
     */
    public static function getRowGenerator(string $filePath): iterable
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'tsv') {
            return self::readCsvFileRows($filePath);   // streaming
        } elseif ($ext === 'json') {
            return self::readJsonFileRows($filePath);  // in-memory
        } elseif ($ext === 'xml') {
            return self::readXmlFileRows($filePath);   // in-memory
        }

        throw new UnsupportedFormatException("Unsupported file format: .$ext");
    }

    private static function readCsvFileRows(string $filePath): iterable
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $delimiter = match ($ext) {
            'csv' => ',',
            'tsv' => "\t",
            default => throw new UnsupportedFormatException("Unsupported file format: $ext"),
        };

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file: $filePath");
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException("Invalid or empty CSV header");
        }
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $assoc = [];
            foreach ($header as $i => $name) {
                if ($name === null || $name === '') {
                    continue;
                }
                $assoc[$name] = isset($row[$i]) ? trim((string)$row[$i]) : null;
            }
            yield $assoc;
        }

        fclose($handle);
    }
    
    private static function readJsonFileRows(string $filePath): iterable
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("Cannot read JSON file: $filePath");
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON format (expected array of objects)");
        }

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($row as $key => $value) {
                $assoc[$key] = is_string($value) ? trim($value) : $value;
            }
            yield $assoc;
        }
    }

    private static function readXmlFileRows(string $filePath): iterable
    {
        $xml = simplexml_load_file($filePath);
        if ($xml === false) {
            throw new RuntimeException("Cannot parse XML file: $filePath");
        }

        foreach ($xml->children() as $productNode) {
            $assoc = [];
            foreach ($productNode->children() as $child) {
                $name  = (string)$child->getName();
                $value = trim((string)$child);
                $assoc[$name] = $value;
            }
            if (!empty($assoc)) {
                yield $assoc;
            }
        }
    }
}
