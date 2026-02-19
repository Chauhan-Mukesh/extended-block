# Extended Block PHP API Reference

This document provides comprehensive documentation for working with the Extended Block data type through the PHP API, similar to how Pimcore's standard Block data type works.

## Table of Contents

- [Overview](#overview)
- [Getting Started](#getting-started)
- [ExtendedBlockContainer API](#extendedblockcontainer-api)
- [ExtendedBlockItem API](#extendedblockitem-api)
- [CRUD Operations](#crud-operations)
- [Per-Item Localization](#per-item-localization)
- [Querying Block Data](#querying-block-data)
- [Working with Multiple Block Types](#working-with-multiple-block-types)
- [Best Practices](#best-practices)
- [Complete Examples](#complete-examples)

## Overview

The Extended Block bundle provides a PHP API that is similar to Pimcore's standard Block data type but stores data in separate database tables instead of serialized JSON. This provides:

- Better query performance
- Direct SQL queryability
- Proper relational data modeling
- Per-item localized data storage (via ExtendedBlockItem methods)

> **Note:** Pimcore's LocalizedFields data type cannot be used inside ExtendedBlock. However, ExtendedBlockItem provides its own localization methods (`setLocalizedValue`, `getLocalizedValue`) for storing per-item translations.

### Key Classes

| Class | Description |
|-------|-------------|
| `ExtendedBlockContainer` | Container holding multiple block items, supports iteration and array access |
| `ExtendedBlockItem` | Individual block item with field values and localized data |
| `ExtendedBlock` | Class definition data type (for admin configuration) |

### Namespace

```php
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
```

## Getting Started

### Accessing Extended Block Data

When you have a Data Object with an Extended Block field, you can access it like any other Pimcore field:

```php
<?php

use Pimcore\Model\DataObject\MyClass;

// Load an object
$object = MyClass::getById(123);

// Get the extended block container (assuming field name is 'contentBlocks')
$container = $object->getContentBlocks();

// Check if there are any items
if (!$container->isEmpty()) {
    // Work with the container
    foreach ($container as $item) {
        echo $item->getType() . "\n";
    }
}
```

### Creating New Block Items

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use Pimcore\Model\DataObject\MyClass;

// Create a new object or get an existing one
$object = new MyClass();
$object->setKey('my-object');
$object->setParentId(1);

// Get the container (it will be created automatically if empty)
$container = $object->getContentBlocks();

// Create a new block item
$item = new ExtendedBlockItem('text_block', 0);
$item->setFieldValue('title', 'My Title');
$item->setFieldValue('content', '<p>My content here</p>');

// Add the item to the container
$container->addItem($item);

// Save the object to persist changes
$object->save();
```

## ExtendedBlockContainer API

The `ExtendedBlockContainer` class implements `Iterator`, `Countable`, and `ArrayAccess` interfaces, making it easy to work with block items.

### Constructor

```php
public function __construct(
    ?Concrete $object = null,
    string $fieldname = '',
    ?ExtendedBlock $definition = null,
    bool $lazyLoad = false
)
```

### Item Management Methods

#### getItems()

Returns all items in the container.

```php
$items = $container->getItems();
// Returns: array<int, ExtendedBlockItem>
```

#### setItems(array $items)

Sets all items, replacing any existing items.

```php
$container->setItems([
    new ExtendedBlockItem('text_block', 0),
    new ExtendedBlockItem('image_block', 1),
]);
```

#### addItem(ExtendedBlockItem $item)

Adds a new item to the end of the container.

```php
$item = new ExtendedBlockItem('text_block', 0);
$container->addItem($item);
```

#### removeItem(int $index)

Removes the item at the specified index. Remaining items are re-indexed.

```php
$container->removeItem(0); // Remove first item
```

#### getItem(int $index)

Gets the item at a specific index.

```php
$item = $container->getItem(0); // Get first item
// Returns: ExtendedBlockItem|null
```

#### moveItem(int $fromIndex, int $toIndex)

Moves an item from one position to another.

```php
// Move item from position 0 to position 2
$container->moveItem(0, 2);
```

### Filtering Methods

#### getItemsByType(string $type)

Filters items by their block type.

```php
$textBlocks = $container->getItemsByType('text_block');
$imageBlocks = $container->getItemsByType('image_block');
```

### Navigation Methods

#### first()

Returns the first item or null if empty.

```php
$firstItem = $container->first();
```

#### last()

Returns the last item or null if empty.

```php
$lastItem = $container->last();
```

### State Methods

#### isEmpty()

Checks if the container has no items.

```php
if ($container->isEmpty()) {
    echo "No block items";
}
```

#### count()

Returns the number of items (implements `Countable`).

```php
$count = count($container);
// or
$count = $container->count();
```

#### clear()

Removes all items from the container.

```php
$container->clear();
```

### Conversion Methods

#### toArray()

Converts the container and all items to an array representation.

```php
$array = $container->toArray();
/*
Returns:
[
    [
        'id' => 1,
        'type' => 'text_block',
        'index' => 0,
        'fieldValues' => ['title' => 'My Title', ...],
        'localizedData' => ['en' => [...], 'de' => [...]]
    ],
    ...
]
*/
```

### Iterator Usage

The container can be used directly in foreach loops:

```php
foreach ($container as $index => $item) {
    echo "Item $index: " . $item->getType() . "\n";
}
```

### Array Access

The container supports array-like access:

```php
// Get item by index
$item = $container[0];

// Check if index exists
if (isset($container[0])) {
    // ...
}

// Add item (append)
$container[] = new ExtendedBlockItem('text_block', 0);

// Set item at specific index
$container[0] = new ExtendedBlockItem('text_block', 0);

// Remove item
unset($container[0]);
```

## ExtendedBlockItem API

The `ExtendedBlockItem` class represents a single block item with its data.

### Constructor

```php
public function __construct(
    string $type = 'default',
    int $index = 0,
    ?Concrete $object = null,
    string $fieldname = ''
)
```

### Basic Properties

#### getId() / setId(int $id)

Get or set the database ID.

```php
$id = $item->getId();
$item->setId(123);
```

#### getType() / setType(string $type)

Get or set the block type identifier.

```php
$type = $item->getType(); // e.g., 'text_block'
$item->setType('image_block');
```

#### getIndex() / setIndex(int $index)

Get or set the position index in the container.

```php
$index = $item->getIndex();
$item->setIndex(5);
```

### Field Value Methods

#### getFieldValue(string $name)

Gets a field value by name.

```php
$title = $item->getFieldValue('title');
$content = $item->getFieldValue('content');
```

#### setFieldValue(string $name, mixed $value)

Sets a field value by name.

```php
$item->setFieldValue('title', 'My Title');
$item->setFieldValue('content', '<p>Some content</p>');
$item->setFieldValue('count', 42);
```

#### hasFieldValue(string $name)

Checks if a field value exists.

```php
if ($item->hasFieldValue('title')) {
    // Field exists
}
```

#### removeFieldValue(string $name)

Removes a field value.

```php
$item->removeFieldValue('obsoleteField');
```

#### getAllFieldValues()

Gets all field values as an associative array.

```php
$values = $item->getAllFieldValues();
// Returns: ['title' => '...', 'content' => '...', ...]
```

### Magic Methods

ExtendedBlockItem supports magic getters and setters for field values:

```php
// Magic setter (equivalent to setFieldValue)
$item->title = 'My Title';
$item->content = '<p>Content</p>';

// Magic getter (equivalent to getFieldValue)
echo $item->title;
echo $item->content;

// Magic isset (equivalent to hasFieldValue)
if (isset($item->title)) {
    // ...
}

// Magic unset (equivalent to removeFieldValue)
unset($item->title);
```

### Localized Data Methods

#### getLocalizedValue(string $language, string $name)

Gets a localized field value.

```php
$enTitle = $item->getLocalizedValue('en', 'headline');
$deTitle = $item->getLocalizedValue('de', 'headline');
```

#### setLocalizedValue(string $language, string $name, mixed $value)

Sets a localized field value.

```php
$item->setLocalizedValue('en', 'headline', 'English Headline');
$item->setLocalizedValue('de', 'headline', 'German Headline');
$item->setLocalizedValue('fr', 'headline', 'French Headline');
```

#### getLocalizedValuesForLanguage(string $language)

Gets all localized values for a specific language.

```php
$enData = $item->getLocalizedValuesForLanguage('en');
// Returns: ['headline' => 'English Headline', 'description' => '...', ...]
```

#### getLocalizedData() / setLocalizedData(array $data)

Gets or sets all localized data.

```php
// Get all localized data
$allLocalized = $item->getLocalizedData();
/*
Returns:
[
    'en' => ['headline' => '...', 'description' => '...'],
    'de' => ['headline' => '...', 'description' => '...'],
]
*/

// Set all localized data at once
$item->setLocalizedData([
    'en' => ['headline' => 'English', 'description' => 'Description EN'],
    'de' => ['headline' => 'Deutsch', 'description' => 'Description DE'],
]);
```

#### hasLocalizedData(string $language, ?string $name = null)

Checks if localized data exists.

```php
// Check if any data exists for a language
if ($item->hasLocalizedData('en')) {
    // ...
}

// Check if a specific field exists for a language
if ($item->hasLocalizedData('en', 'headline')) {
    // ...
}
```

#### clearLocalizedData(string $language)

Clears all localized data for a specific language.

```php
$item->clearLocalizedData('en');
```

### Conversion Methods

#### toArray()

Converts the item to an array representation.

```php
$array = $item->toArray();
/*
Returns:
[
    'id' => 1,
    'type' => 'text_block',
    'index' => 0,
    'fieldValues' => ['title' => '...', 'content' => '...'],
    'localizedData' => ['en' => [...], 'de' => [...]]
]
*/
```

#### fromArray(array $data, ?Concrete $object = null, string $fieldname = '')

Creates an item from array data (static factory method).

```php
$item = ExtendedBlockItem::fromArray([
    'type' => 'text_block',
    'index' => 0,
    'fieldValues' => [
        'title' => 'My Title',
        'content' => 'My Content'
    ],
    'localizedData' => [
        'en' => ['headline' => 'English Headline'],
        'de' => ['headline' => 'German Headline']
    ]
]);
```

### Modified State

#### isModified() / setModified(bool $modified)

Track whether the item has been modified.

```php
if ($item->isModified()) {
    // Item has unsaved changes
}

// Reset modified state
$item->setModified(false);
```

## CRUD Operations

### Creating Block Items

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Model\DataObject\Product;

$product = Product::getById(123);
$container = $product->getContentBlocks();

// Create and configure a new item
$textBlock = new ExtendedBlockItem('text_block', 0);
$textBlock->setFieldValue('title', 'Product Description');
$textBlock->setFieldValue('content', '<p>This is a great product!</p>');

// Add localized content
$textBlock->setLocalizedValue('en', 'headline', 'Features');
$textBlock->setLocalizedValue('de', 'headline', 'Eigenschaften');

// Add to container
$container->addItem($textBlock);

// Save to database
$product->save();
```

### Reading Block Items

```php
<?php

use Pimcore\Model\DataObject\Product;

$product = Product::getById(123);
$container = $product->getContentBlocks();

// Iterate through all items
foreach ($container as $item) {
    echo "Type: " . $item->getType() . "\n";
    echo "Title: " . $item->getFieldValue('title') . "\n";
    
    // Get localized content
    echo "EN Headline: " . $item->getLocalizedValue('en', 'headline') . "\n";
    echo "DE Headline: " . $item->getLocalizedValue('de', 'headline') . "\n";
    echo "---\n";
}

// Access specific items
$firstItem = $container->first();
$lastItem = $container->last();
$thirdItem = $container->getItem(2); // 0-indexed

// Filter by type
$textBlocks = $container->getItemsByType('text_block');
foreach ($textBlocks as $textBlock) {
    echo $textBlock->getFieldValue('title') . "\n";
}
```

### Updating Block Items

```php
<?php

use Pimcore\Model\DataObject\Product;

$product = Product::getById(123);
$container = $product->getContentBlocks();

// Update first item
$firstItem = $container->first();
if ($firstItem) {
    $firstItem->setFieldValue('title', 'Updated Title');
    $firstItem->setLocalizedValue('en', 'headline', 'Updated Headline');
}

// Update specific item by index
$item = $container->getItem(1);
if ($item) {
    $item->content = 'Updated content using magic setter';
}

// Save changes
$product->save();
```

### Deleting Block Items

```php
<?php

use Pimcore\Model\DataObject\Product;

$product = Product::getById(123);
$container = $product->getContentBlocks();

// Remove by index
$container->removeItem(0); // Removes first item

// Remove all items of a specific type
$itemsToRemove = [];
foreach ($container as $index => $item) {
    if ($item->getType() === 'deprecated_block') {
        $itemsToRemove[] = $index;
    }
}
// Remove in reverse order to maintain correct indices
rsort($itemsToRemove);
foreach ($itemsToRemove as $index) {
    $container->removeItem($index);
}

// Clear all items
$container->clear();

// Save changes
$product->save();
```

## Per-Item Localization

ExtendedBlockItem provides built-in methods for storing localized data per block item. This is different from Pimcore's LocalizedFields data type (which is NOT supported inside ExtendedBlock). Instead, ExtendedBlockItem has its own localization methods.

> **Important:** This is NOT the same as Pimcore's LocalizedFields. You cannot add a LocalizedFields container inside ExtendedBlock. The localization methods here store data in a separate localized table specific to ExtendedBlock.

### Setting Up Localized Content

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;

$item = new ExtendedBlockItem('content_block', 0);

// Set regular field values (stored in main table)
$item->setFieldValue('image', '/path/to/image.jpg');
$item->setFieldValue('link', '/products/detail');

// Set per-item localized values (stored in localized table)
$item->setLocalizedValue('en', 'title', 'English Title');
$item->setLocalizedValue('en', 'description', 'English description text');

$item->setLocalizedValue('de', 'title', 'Deutscher Titel');
$item->setLocalizedValue('de', 'description', 'Deutscher Beschreibungstext');

$item->setLocalizedValue('fr', 'title', 'Titre Français');
$item->setLocalizedValue('fr', 'description', 'Texte de description français');
```

### Working with Pimcore's Language Context

```php
<?php

use Pimcore\Model\DataObject\Product;
use Pimcore\Tool;

$product = Product::getById(123);
$container = $product->getContentBlocks();

// Get valid languages from Pimcore
$languages = Tool::getValidLanguages();

foreach ($container as $item) {
    echo "Block Type: " . $item->getType() . "\n";
    
    foreach ($languages as $lang) {
        $title = $item->getLocalizedValue($lang, 'title');
        if ($title) {
            echo "  [$lang] $title\n";
        }
    }
}
```

### Bulk Localized Data Operations

```php
<?php

// Set all localized data at once
$item->setLocalizedData([
    'en' => [
        'title' => 'English Title',
        'description' => 'English description',
        'cta_text' => 'Learn More'
    ],
    'de' => [
        'title' => 'Deutscher Titel',
        'description' => 'Deutsche Beschreibung',
        'cta_text' => 'Mehr erfahren'
    ],
]);

// Get all localized data
$allLocalizedData = $item->getLocalizedData();

// Get all data for a specific language
$germanData = $item->getLocalizedValuesForLanguage('de');
```

## Querying Block Data

Since Extended Block stores data in separate database tables, you can query the data directly using SQL.

### Table Naming Convention

- Main table: `object_eb_{classId}_{fieldName}`
- Localized table: `object_eb_{classId}_{fieldName}_localized`

### Direct Database Queries

```php
<?php

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;

// Get database connection
$db = Db::get();

// Get class ID for your class
$class = ClassDefinition::getByName('Product');
$classId = $class->getId();

// Query main block data
$tableName = 'object_eb_' . $classId . '_contentBlocks';
$items = $db->fetchAllAssociative(
    "SELECT * FROM `{$tableName}` WHERE type = ? ORDER BY `index`",
    ['text_block']
);

// Query with localized data (join)
$localizedTable = $tableName . '_localized';
$results = $db->fetchAllAssociative(
    "SELECT m.*, l.title as localized_title, l.language 
     FROM `{$tableName}` m
     LEFT JOIN `{$localizedTable}` l ON m.id = l.ooo_id
     WHERE m.o_id = ? AND l.language = ?",
    [123, 'en']
);
```

### Finding Objects by Block Content

```php
<?php

use Pimcore\Db;
use Pimcore\Model\DataObject\Product;

$db = Db::get();
$classId = Product::classId();
$tableName = 'object_eb_' . $classId . '_contentBlocks';

// Find all objects with text blocks containing specific content
$objectIds = $db->fetchFirstColumn(
    "SELECT DISTINCT o_id FROM `{$tableName}` 
     WHERE type = 'text_block' AND title LIKE ?",
    ['%promotion%']
);

// Load the objects
foreach ($objectIds as $objectId) {
    $product = Product::getById($objectId);
    if ($product) {
        echo $product->getName() . "\n";
    }
}
```

### Aggregation Queries

```php
<?php

use Pimcore\Db;
use Pimcore\Model\DataObject\Product;

$db = Db::get();
$classId = Product::classId();
$tableName = 'object_eb_' . $classId . '_contentBlocks';

// Count blocks by type
$counts = $db->fetchAllAssociative(
    "SELECT type, COUNT(*) as count FROM `{$tableName}` GROUP BY type"
);

// Find objects with most blocks
$topObjects = $db->fetchAllAssociative(
    "SELECT o_id, COUNT(*) as block_count 
     FROM `{$tableName}` 
     GROUP BY o_id 
     ORDER BY block_count DESC 
     LIMIT 10"
);
```

## Working with Multiple Block Types

Extended Block supports multiple block types, each with different field configurations.

### Example Block Type Structure

> **Note:** LocalizedFields data type is NOT allowed inside ExtendedBlock. Use ExtendedBlockItem's per-item localization methods instead.

```
contentBlocks (Extended Block Field)
├── text_block
│   ├── title (Input)
│   ├── content (WYSIWYG)
│   └── description (Textarea)
├── image_block
│   ├── image (Image)
│   ├── caption (Input)
│   └── link (Link)
└── video_block
    ├── videoUrl (Input)
    ├── thumbnail (Image)
    └── autoplay (Checkbox)
```

For per-item localized content, use ExtendedBlockItem's localization methods:
```php
$textBlock->setLocalizedValue('en', 'headline', 'Welcome');
$textBlock->setLocalizedValue('de', 'headline', 'Willkommen');
```

### Creating Different Block Types

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;

// Create a text block
$textBlock = new ExtendedBlockItem('text_block', 0);
$textBlock->setFieldValue('title', 'Welcome Section');
$textBlock->setFieldValue('content', '<p>Welcome to our site!</p>');
$textBlock->setLocalizedValue('en', 'headline', 'Welcome');
$textBlock->setLocalizedValue('de', 'headline', 'Willkommen');

// Create an image block
$imageBlock = new ExtendedBlockItem('image_block', 1);
$imageBlock->setFieldValue('image', $assetId); // Pimcore Asset ID
$imageBlock->setFieldValue('caption', 'Product Hero Image');
$imageBlock->setFieldValue('link', '/products');

// Create a video block
$videoBlock = new ExtendedBlockItem('video_block', 2);
$videoBlock->setFieldValue('videoUrl', 'https://youtube.com/watch?v=...');
$videoBlock->setFieldValue('thumbnail', $thumbnailAssetId);
$videoBlock->setFieldValue('autoplay', false);

// Add all to container
$container->addItem($textBlock);
$container->addItem($imageBlock);
$container->addItem($videoBlock);
```

### Processing Different Block Types

```php
<?php

use Pimcore\Model\DataObject\Product;

$product = Product::getById(123);
$container = $product->getContentBlocks();

foreach ($container as $item) {
    switch ($item->getType()) {
        case 'text_block':
            echo "<div class=\"text-block\">\n";
            echo "<h2>" . htmlspecialchars($item->getFieldValue('title')) . "</h2>\n";
            echo $item->getFieldValue('content') . "\n";
            echo "</div>\n";
            break;
            
        case 'image_block':
            $assetId = $item->getFieldValue('image');
            $asset = \Pimcore\Model\Asset::getById($assetId);
            echo "<figure class=\"image-block\">\n";
            if ($asset) {
                echo "<img src=\"" . $asset->getFullPath() . "\" />\n";
            }
            echo "<figcaption>" . htmlspecialchars($item->getFieldValue('caption')) . "</figcaption>\n";
            echo "</figure>\n";
            break;
            
        case 'video_block':
            $autoplay = $item->getFieldValue('autoplay') ? 'autoplay' : '';
            echo "<div class=\"video-block\" data-autoplay=\"{$autoplay}\">\n";
            echo "<video src=\"" . htmlspecialchars($item->getFieldValue('videoUrl')) . "\"></video>\n";
            echo "</div>\n";
            break;
    }
}
```

### Type-Specific Retrieval

```php
<?php

$container = $product->getContentBlocks();

// Get all text blocks
$textBlocks = $container->getItemsByType('text_block');
echo "Found " . count($textBlocks) . " text blocks\n";

// Get all image blocks
$imageBlocks = $container->getItemsByType('image_block');
foreach ($imageBlocks as $imageBlock) {
    echo "Image: " . $imageBlock->getFieldValue('caption') . "\n";
}

// Check if specific type exists
$hasVideo = count($container->getItemsByType('video_block')) > 0;
```

## Best Practices

### 1. Use Lazy Loading for Performance

Lazy loading is enabled by default and loads data only when accessed:

```php
// The container is created but data isn't loaded yet
$container = $product->getContentBlocks();

// Data is loaded now when first accessed
foreach ($container as $item) {
    // ...
}
```

### 2. Batch Operations

When adding multiple items, add them all before saving:

```php
$container = $product->getContentBlocks();

// Add multiple items
foreach ($data as $blockData) {
    $item = new ExtendedBlockItem($blockData['type'], 0);
    // Configure item...
    $container->addItem($item);
}

// Save once at the end
$product->save();
```

### 3. Use Transactions for Complex Operations

For complex operations involving multiple objects:

```php
use Pimcore\Db;

$db = Db::get();
$db->beginTransaction();

try {
    // Multiple operations
    foreach ($products as $product) {
        $container = $product->getContentBlocks();
        // Modify...
        $product->save();
    }
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### 4. Check for Empty Containers

Always check before iterating:

```php
$container = $product->getContentBlocks();

if (!$container->isEmpty()) {
    foreach ($container as $item) {
        // Process items
    }
} else {
    echo "No content blocks configured";
}
```

### 5. Handle Missing Fields Gracefully

```php
// Always check for null values
$title = $item->getFieldValue('title');
if ($title !== null) {
    echo $title;
}

// Or use a default value
$content = $item->getFieldValue('content') ?? '<p>Default content</p>';
```

### 6. Use Type Hints in Your Code

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;

function processContainer(ExtendedBlockContainer $container): void
{
    foreach ($container as $item) {
        processItem($item);
    }
}

function processItem(ExtendedBlockItem $item): array
{
    return [
        'type' => $item->getType(),
        'title' => $item->getFieldValue('title'),
    ];
}
```

## Complete Examples

### Example 1: Building a Page with Multiple Content Blocks

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Model\DataObject\Page;

// Create a new page
$page = new Page();
$page->setKey('about-us');
$page->setParentId(1);
$page->setPublished(true);

$container = $page->getContentBlocks();

// Hero section
$hero = new ExtendedBlockItem('hero_block', 0);
$hero->setFieldValue('backgroundImage', 1234); // Asset ID
$hero->setLocalizedValue('en', 'title', 'About Our Company');
$hero->setLocalizedValue('en', 'subtitle', 'Learn more about what makes us unique');
$hero->setLocalizedValue('de', 'title', 'Über unser Unternehmen');
$hero->setLocalizedValue('de', 'subtitle', 'Erfahren Sie mehr über das, was uns einzigartig macht');
$container->addItem($hero);

// Text content section
$textSection = new ExtendedBlockItem('text_block', 1);
$textSection->setFieldValue('layout', 'full-width');
$textSection->setLocalizedValue('en', 'content', '<p>Our company was founded in 2010...</p>');
$textSection->setLocalizedValue('de', 'content', '<p>Unser Unternehmen wurde 2010 gegründet...</p>');
$container->addItem($textSection);

// Team gallery
$teamGallery = new ExtendedBlockItem('gallery_block', 2);
$teamGallery->setFieldValue('title', 'Our Team');
$teamGallery->setFieldValue('images', [5678, 5679, 5680]); // Array of Asset IDs
$container->addItem($teamGallery);

// Contact CTA
$contactCta = new ExtendedBlockItem('cta_block', 3);
$contactCta->setFieldValue('link', '/contact');
$contactCta->setLocalizedValue('en', 'buttonText', 'Get in Touch');
$contactCta->setLocalizedValue('de', 'buttonText', 'Kontaktieren Sie uns');
$container->addItem($contactCta);

$page->save();
```

### Example 2: Rendering Blocks in a Twig Template Controller

```php
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageController extends FrontendController
{
    public function detailAction(Request $request): Response
    {
        $page = Page::getById($request->get('id'));
        
        if (!$page || !$page->getPublished()) {
            throw $this->createNotFoundException('Page not found');
        }
        
        // Prepare block data for template
        $blocks = [];
        $container = $page->getContentBlocks();
        $currentLanguage = $request->getLocale();
        
        foreach ($container as $item) {
            $blockData = [
                'type' => $item->getType(),
                'index' => $item->getIndex(),
                'fields' => $item->getAllFieldValues(),
                'localized' => $item->getLocalizedValuesForLanguage($currentLanguage),
            ];
            $blocks[] = $blockData;
        }
        
        return $this->render('page/detail.html.twig', [
            'page' => $page,
            'blocks' => $blocks,
        ]);
    }
}
```

### Example 3: Importing Content from JSON

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Model\DataObject\Article;

function importContentBlocks(Article $article, array $jsonData): void
{
    $container = $article->getContentBlocks();
    $container->clear(); // Remove existing blocks
    
    foreach ($jsonData['blocks'] as $index => $blockData) {
        $item = ExtendedBlockItem::fromArray([
            'type' => $blockData['type'],
            'index' => $index,
            'fieldValues' => $blockData['fields'] ?? [],
            'localizedData' => $blockData['translations'] ?? [],
        ]);
        
        $container->addItem($item);
    }
    
    $article->save();
}

// Usage
$jsonData = [
    'blocks' => [
        [
            'type' => 'text_block',
            'fields' => ['title' => 'Introduction'],
            'translations' => [
                'en' => ['content' => 'English content'],
                'de' => ['content' => 'German content'],
            ]
        ],
        [
            'type' => 'image_block',
            'fields' => ['image' => 1234, 'caption' => 'Main image']
        ]
    ]
];

$article = Article::getById(123);
importContentBlocks($article, $jsonData);
```

### Example 4: Exporting Content to JSON

```php
<?php

use Pimcore\Model\DataObject\Article;

function exportContentBlocks(Article $article): array
{
    $container = $article->getContentBlocks();
    
    $export = [
        'articleId' => $article->getId(),
        'articleKey' => $article->getKey(),
        'blocks' => []
    ];
    
    foreach ($container as $item) {
        $export['blocks'][] = [
            'type' => $item->getType(),
            'index' => $item->getIndex(),
            'fields' => $item->getAllFieldValues(),
            'translations' => $item->getLocalizedData(),
        ];
    }
    
    return $export;
}

// Usage
$article = Article::getById(123);
$exportData = exportContentBlocks($article);
$json = json_encode($exportData, JSON_PRETTY_PRINT);
file_put_contents('/tmp/article-export.json', $json);
```

### Example 5: Cloning Blocks Between Objects

```php
<?php

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Model\DataObject\Page;

function cloneBlocks(Page $source, Page $target): void
{
    $sourceContainer = $source->getContentBlocks();
    $targetContainer = $target->getContentBlocks();
    
    // Clear target
    $targetContainer->clear();
    
    // Clone each block
    foreach ($sourceContainer as $sourceItem) {
        $clonedItem = ExtendedBlockItem::fromArray(
            $sourceItem->toArray(),
            $target,
            'contentBlocks'
        );
        
        // Reset ID so it gets a new one on save
        $clonedItem->setId(null);
        
        $targetContainer->addItem($clonedItem);
    }
    
    $target->save();
}

// Usage
$originalPage = Page::getById(123);
$newPage = new Page();
$newPage->setKey('cloned-page');
$newPage->setParentId(1);
$newPage->save();

cloneBlocks($originalPage, $newPage);
```

---

For more information, see the main [README.md](../README.md) or explore the source code documentation.
