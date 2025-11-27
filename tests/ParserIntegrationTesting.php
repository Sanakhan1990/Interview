<?php

use PHPUnit\Framework\TestCase;
use App\FileReader;

require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/FileReader.php';
require_once __DIR__ . '/../config/config.php';

class ParserIntegrationTesting extends TestCase
{
    public function testFullParsingFlow()
    {
        // Create a temporary CSV file for testing
        $csv = __DIR__ . '/sample.csv';

        file_put_contents($csv, <<<CSV
brand_name,model_name,colour_name,gb_spec_name,network_name,grade_name,condition_name
Apple,iPhone X,Black,64GB,Unlocked,Grade A,Working
Apple,iPhone X,Black,64GB,Unlocked,Grade A,Working
Samsung,Galaxy S21,White,128GB,Unlocked,Grade B,Working
CSV
        );

        $generator = FileReader::getRowGenerator($csv);

        $aggregatedCounts = [];

        foreach ($generator as $row) {
            $product = map_row_to_product($row);
            add_to_aggregated_counts($aggregatedCounts, $product);
        }

        $this->assertCount(2, $aggregatedCounts);

        // Check counts for each combination
        $counts = array_values($aggregatedCounts);
        $this->assertEquals(2, $counts[0]); // Apple iPhone X
        $this->assertEquals(1, $counts[1]); // Samsung Galaxy S21

        // Clean up temp CSV
        unlink($csv);
    }
}
