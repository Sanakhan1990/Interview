# Supplier Product List Processor – Quick Start

TheBigPhoneStore/Interview/
├─ config/
|  ├─ config.php           # Contains the config for 
├─ example/                # Example CSV/TSV input files
│  ├─ products_comma_seperated.csv # Products in Comma Seperated File
│  ├─ products_tab_seperated.tsv   # Products in Tab Seperated File
├─ src/                    # Core PHP source code
│  ├─ functions.php        # Core parsing and aggregation functions
│  ├─ FileReader.php       # File reading & generator logic
├─ tests/                  # PHPUnit/Pest integration tests
│  ├─ ParserIntegrationTest.php
├─ composer.json           # Composer autoload and dependencies
├─ parser.php              # CLI entry point
├─ phpunit.xml             # Integration Testing
├─ README.md               # Project README


**Overview:**Processes supplier product lists (CSV/TSV) and generates **unique product combinations** with counts. Built with **native PHP 7+**, CLI-based, memory-efficient, and extendable to JSON/XML.


## Requirements

* PHP 7.4+
* Composer (for autoloading and testing)
* Terminal/Command Line access

---

## Running the Application

1. Place your input file (`.csv` or `.tsv`) in the project directory.

2. Run the parser from the terminal:

```bash
php parser.php --file=INPUT_FILE --unique-combinations=OUTPUT_FILE
```

**Examples:**

```bash
php parser.php --file=example/products_comma_separated.csv --unique-combinations=unique_counts.csv
php parser.php --file=example/products_tab_separated.tsv --unique-combinations=unique_counts.csv
```

**What it does:**

* Reads each row from the input file
* Normalizes product fields (`make`, `model`, `colour`, `capacity`, `network`, `grade`, `condition`)
* Prints JSON representation of each product row
* Aggregates and writes unique product combinations with counts to `OUTPUT_FILE`

---

## Notes

* **Required fields:** `make` and `model`
* **Optional fields:** `colour`, `capacity`, `network`, `grade`, `condition`
* Missing required fields will throw a `MissingRequiredFieldException`
* Works efficiently for large files using PHP generators
* Future formats like JSON/XML can be added easily

---

## Testing (Optional)

Run integration tests using PHPUnit:

```bash
vendor/bin/phpunit tests/ParserIntegrationTest.php
```
**Examples:**

```bash
php vendor/bin/phpunit tests/ParserIntegrationTest.php
