# Extended Block Bundle for Pimcore

[![Latest Version](https://img.shields.io/packagist/v/chauhan-mukesh/extended-block-bundle.svg)](https://packagist.org/packages/chauhan-mukesh/extended-block-bundle)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://php.net/)
[![Pimcore Version](https://img.shields.io/badge/pimcore-%3E%3D10.0-00ADD8.svg)](https://pimcore.com/)

A Pimcore bundle that extends the block data type by storing data in separate database tables instead of serialized JSON in a single column. This provides better performance, queryability, and proper relational data modeling.

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [PHP API](#-php-api)
- [Architecture](#-architecture)
- [Database Schema](#-database-schema)
- [Localized Fields](#-localized-fields)
- [Block Nesting Rules](#-block-nesting-rules)
- [API Reference](#-api-reference)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)
- [Documentation](#-documentation)

## ✨ Features

- **Separate Table Storage**: Each extended block stores data in dedicated database tables, similar to field collections
- **Better Performance**: Eliminates serialization overhead and enables efficient database queries
- **Full Queryability**: Block data can be queried directly using SQL
- **Localized Field Support**: Full support for localized fields within block items
- **Nesting Prevention**: Automatic validation prevents invalid block nesting configurations
- **Safe Schema Updates**: New fields can be added without data loss; removed fields preserve existing data
- **Lazy Loading**: Optional lazy loading for improved performance with large datasets
- **Complete Admin UI**: Full Pimcore admin integration with drag-and-drop ordering
- **Multiple Block Types**: Define multiple block types with different field configurations
- **Migration Tools**: Tools for migrating from standard Block type

## 📦 Requirements

- PHP 8.0 or higher
- Pimcore 10.0 or higher (supports Pimcore 10.x and 11.x)
- Symfony 5.4 or higher (5.x for Pimcore 10, 6.x for Pimcore 11)

## 🚀 Installation

### Step 1: Install via Composer

```bash
composer require chauhan-mukesh/extended-block-bundle
```

### Step 2: Enable the Bundle

Add the bundle to your `config/bundles.php`:

```php
return [
    // ... other bundles
    ExtendedBlockBundle\ExtendedBlockBundle::class => ['all' => true],
];
```

### Step 3: Install the Bundle

Run the installer to set up required database tables:

```bash
bin/console pimcore:bundle:install ExtendedBlockBundle
```

### Step 4: Publish Assets

```bash
bin/console assets:install public --symlink
```

## ⚙️ Configuration

Configure the bundle in `config/packages/extended_block.yaml`:

```yaml
extended_block:
    # Prefix for database tables (default: 'object_eb_')
    table_prefix: 'object_eb_'
    
    # Enable localized fields support (default: true)
    enable_localized_fields: true
    
    # Enable strict validation mode (default: true)
    strict_mode: true
    
    # Maximum items per block (null for unlimited)
    max_items: null
    
    # Enable query logging for debugging (default: false)
    debug_queries: false
```

## 📖 Usage

### Adding ExtendedBlock to a Class Definition

1. Open the Pimcore Admin panel
2. Navigate to **Settings > Data Objects > Classes**
3. Select or create a class
4. Add a new field and select **Extended Block** from the data types
5. Configure the block types and fields in the settings panel

### Defining Block Types

In the class editor, you can define multiple block types, each with its own set of fields:

```
Block Type: text_block
├── title (Input)
├── content (WYSIWYG)
└── LocalizedFields
    ├── headline (Input)
    └── description (Textarea)

Block Type: image_block
├── image (Image)
├── caption (Input)
└── link (Link)
```

### Working with Extended Block Data

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;

// Get extended block data from an object
$container = $object->getMyExtendedBlock();

// Check if container has items
if (!$container->isEmpty()) {
    
    // Iterate over items
    foreach ($container as $item) {
        echo $item->getType();           // e.g., 'text_block'
        echo $item->getFieldValue('title');
        
        // Get localized value
        echo $item->getLocalizedValue('en', 'headline');
    }
    
    // Get first/last item
    $first = $container->first();
    $last = $container->last();
    
    // Get items by type
    $textBlocks = $container->getItemsByType('text_block');
    
    // Count items
    echo count($container);
}

// Create a new item
$newItem = new ExtendedBlockItem('text_block', 0);
$newItem->setFieldValue('title', 'My Title');
$newItem->setLocalizedValue('en', 'headline', 'English Headline');
$newItem->setLocalizedValue('de', 'headline', 'German Headline');

// Add item to container
$container->addItem($newItem);

// Remove item at index
$container->removeItem(0);

// Move item
$container->moveItem(0, 2);

// Clear all items
$container->clear();

// Save the object
$object->save();
```

### Using Magic Methods

ExtendedBlockItem supports magic getters and setters:

```php
// Magic setter
$item->title = 'My Title';
$item->content = '<p>Some content</p>';

// Magic getter
echo $item->title;
echo $item->content;

// Magic isset
if (isset($item->title)) {
    // ...
}
```

## 🔧 PHP API

The Extended Block bundle provides a PHP API that works similarly to Pimcore's standard Block data type. For comprehensive documentation, see [doc/PHP_API.md](doc/PHP_API.md).

### Quick Start

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Model\DataObject\Product;

// Get an object with an extended block field
$product = Product::getById(123);

// Access the extended block container
$container = $product->getContentBlocks();

// Create and add a new block item
$item = new ExtendedBlockItem('text_block', 0);
$item->setFieldValue('title', 'Product Features');
$item->setFieldValue('content', '<p>Amazing features...</p>');

// Set localized content
$item->setLocalizedValue('en', 'headline', 'Features');
$item->setLocalizedValue('de', 'headline', 'Eigenschaften');

$container->addItem($item);
$product->save();
```

### Key Classes

| Class | Namespace | Description |
|-------|-----------|-------------|
| `ExtendedBlockContainer` | `ExtendedBlockBundle\Model\DataObject\Data` | Container for block items with iteration and array access support |
| `ExtendedBlockItem` | `ExtendedBlockBundle\Model\DataObject\Data` | Individual block item with field values and localized data |

### Common Operations

#### Reading Block Data

```php
$container = $product->getContentBlocks();

// Iterate through items
foreach ($container as $item) {
    echo $item->getType();                    // Block type
    echo $item->getFieldValue('title');       // Field value
    echo $item->getLocalizedValue('en', 'headline'); // Localized value
}

// Access specific items
$first = $container->first();
$last = $container->last();
$byIndex = $container->getItem(2);

// Filter by type
$textBlocks = $container->getItemsByType('text_block');
```

#### Creating and Modifying Blocks

```php
// Create new item
$item = new ExtendedBlockItem('image_block', 0);
$item->setFieldValue('image', $assetId);
$item->setFieldValue('caption', 'Product Image');

// Using magic methods
$item->title = 'My Title';
echo $item->title;

// Add to container
$container->addItem($item);

// Modify existing item
$firstItem = $container->first();
$firstItem->setFieldValue('title', 'Updated Title');

// Remove and reorder
$container->removeItem(0);
$container->moveItem(0, 2);
```

#### Direct Database Queries

Since data is stored in separate tables, you can query directly:

```php
use Pimcore\Db;

$db = Db::get();
$classId = Product::classId();
$tableName = 'object_eb_' . $classId . '_contentBlocks';

// Find objects with specific content
$objectIds = $db->fetchFirstColumn(
    "SELECT DISTINCT o_id FROM `{$tableName}` WHERE title LIKE ?",
    ['%promotion%']
);
```

For more detailed examples including CRUD operations, localized fields, working with multiple block types, and best practices, see the [PHP API Documentation](doc/PHP_API.md).

## 🏗️ Architecture

### Class Diagram

```
ExtendedBlockBundle
├── ExtendedBlockBundle.php              # Main bundle class
├── DependencyInjection/
│   ├── ExtendedBlockExtension.php       # DI extension
│   └── Configuration.php                # Bundle configuration
├── Model/
│   └── DataObject/
│       ├── ClassDefinition/
│       │   └── Data/
│       │       └── ExtendedBlock.php    # Data type definition
│       └── Data/
│           ├── ExtendedBlockContainer.php  # Container class
│           └── ExtendedBlockItem.php       # Item class
├── Service/
│   └── TableSchemaService.php           # Schema management
├── EventListener/
│   └── ClassDefinitionListener.php      # Class definition events
├── Installer/
│   └── ExtendedBlockInstaller.php       # Bundle installer
└── Resources/
    ├── config/
    │   └── services.yaml                # Service definitions
    └── public/
        ├── js/                          # Admin JavaScript
        └── css/                         # Admin CSS
```

## 🗄️ Database Schema

### Main Table Structure

For each extended block field, a table is created:

```sql
CREATE TABLE `object_eb_{classId}_{fieldName}` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `o_id` INT(11) UNSIGNED NOT NULL,      -- Reference to parent object
    `fieldname` VARCHAR(70) NOT NULL,       -- Field name
    `index` INT(11) UNSIGNED NOT NULL,      -- Position in block
    `type` VARCHAR(100) NOT NULL,           -- Block type identifier
    -- ... field columns based on definition
    PRIMARY KEY (`id`),
    INDEX `idx_object` (`o_id`),
    INDEX `idx_fieldname` (`fieldname`)
);
```

### Localized Table Structure

For localized fields within blocks:

```sql
CREATE TABLE `object_eb_{classId}_{fieldName}_localized` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ooo_id` INT(11) UNSIGNED NOT NULL,     -- Reference to main item
    `language` VARCHAR(10) NOT NULL,         -- Language code
    -- ... localized field columns
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_item_language` (`ooo_id`, `language`)
);
```

## 🌍 Localized Fields

### Configuration

ExtendedBlock supports localized fields within block items:

```php
// In class definition, add LocalizedFields to a block type:
Block Type: content_block
├── image (Image)
└── LocalizedFields
    ├── title (Input)
    └── description (Textarea)
```

### Usage

```php
// Set localized values
$item->setLocalizedValue('en', 'title', 'English Title');
$item->setLocalizedValue('de', 'title', 'German Title');
$item->setLocalizedValue('fr', 'title', 'French Title');

// Get localized values
$enTitle = $item->getLocalizedValue('en', 'title');
$deTitle = $item->getLocalizedValue('de', 'title');

// Get all localized data for a language
$enData = $item->getLocalizedValuesForLanguage('en');

// Get all localized data
$allLocalized = $item->getLocalizedData();
```

### Nesting Prevention

To prevent infinite recursion, ExtendedBlock with LocalizedFields cannot be placed inside a LocalizedFields container:

```
❌ Invalid:
LocalizedFields
└── ExtendedBlock (with LocalizedFields inside)
    └── LocalizedFields  # Would cause recursion!

✅ Valid:
ExtendedBlock
└── LocalizedFields
    └── title
    └── description
```

## 🚫 Block Nesting Rules

To ensure data integrity and prevent performance issues, the following nesting configurations are **not allowed**:

### Disallowed Configurations

| Configuration | Reason |
|--------------|--------|
| ExtendedBlock inside ExtendedBlock | Could cause infinite recursion and complex data relationships |
| Block inside ExtendedBlock | Standard Block uses different storage mechanism |
| ExtendedBlock inside Block | ExtendedBlock requires separate table storage |
| Block inside LocalizedFields within ExtendedBlock | Block nesting is not supported |
| ExtendedBlock with LocalizedFields inside LocalizedFields | Would cause table relationship conflicts |

### Examples

```
❌ Invalid: ExtendedBlock nesting
contentBlocks (ExtendedBlock)
└── text_block
    └── nestedBlocks (ExtendedBlock)  # Not allowed!

❌ Invalid: Block inside ExtendedBlock
contentBlocks (ExtendedBlock)
└── text_block
    └── innerBlock (Block)  # Not allowed!

❌ Invalid: ExtendedBlock inside Block  
mainBlock (Block)
└── contentBlocks (ExtendedBlock)  # Not allowed!

✅ Valid: Flat ExtendedBlock with various fields
contentBlocks (ExtendedBlock)
├── text_block
│   ├── title (Input)
│   ├── content (WYSIWYG)
│   └── LocalizedFields
│       ├── headline (Input)
│       └── teaser (Textarea)
└── image_block
    ├── image (Image)
    └── caption (Input)
```

### Validation

The bundle automatically validates your class definition when saving and will throw an exception if any invalid nesting is detected. This validation occurs both in the admin UI and via the PHP API.

## 📚 API Reference

### ExtendedBlockContainer

| Method | Description |
|--------|-------------|
| `getItems()` | Get all block items |
| `setItems(array $items)` | Set all block items |
| `addItem(ExtendedBlockItem $item)` | Add an item |
| `removeItem(int $index)` | Remove item at index |
| `getItem(int $index)` | Get item at index |
| `moveItem(int $from, int $to)` | Move item position |
| `getItemsByType(string $type)` | Filter items by type |
| `first()` | Get first item |
| `last()` | Get last item |
| `clear()` | Remove all items |
| `isEmpty()` | Check if container is empty |
| `count()` | Count items (Countable) |
| `toArray()` | Convert to array |

### ExtendedBlockItem

| Method | Description |
|--------|-------------|
| `getId()` | Get database ID |
| `getType()` | Get block type |
| `getIndex()` | Get position index |
| `getFieldValue(string $name)` | Get field value |
| `setFieldValue(string $name, $value)` | Set field value |
| `hasFieldValue(string $name)` | Check if field exists |
| `getLocalizedValue(string $lang, string $name)` | Get localized value |
| `setLocalizedValue(string $lang, string $name, $value)` | Set localized value |
| `getLocalizedValuesForLanguage(string $lang)` | Get all values for language |
| `getLocalizedData()` | Get all localized data |
| `setLocalizedData(array $data)` | Set all localized data |
| `toArray()` | Convert to array |
| `fromArray(array $data)` | Create from array |

## 🧪 Testing

Run the test suite:

```bash
# Install dev dependencies
composer install

# Run PHPUnit tests
composer test

# Run with coverage
composer test -- --coverage-html coverage

# Run coding standards check
composer cs-check

# Fix coding standards
composer cs-fix

# Run PHPStan static analysis
composer phpstan
```

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and coding standards checks
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Coding Standards

This project follows:
- [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html)
- PHPStan level 6 for static analysis

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Chauhan Mukesh**

## 🙏 Acknowledgments

- [Pimcore](https://pimcore.com/) for the amazing platform
- [Symfony](https://symfony.com/) for the robust framework
- All contributors who help improve this bundle

## 📖 Documentation

For more detailed documentation, refer to the following resources:

| Document | Description |
|----------|-------------|
| [PHP API Reference](doc/PHP_API.md) | Comprehensive guide for working with Extended Block through PHP API |
| [README.md](README.md) | This file - overview and quick start guide |

### Additional Resources

- [Pimcore Data Objects Documentation](https://pimcore.com/docs/platform/Pimcore/DataObjects/)
- [Pimcore Block Data Type](https://pimcore.com/docs/platform/Pimcore/Documents/Editables/Block/)

---

Made with ❤️ for the Pimcore community