# Changelog

All notable changes to the Extended Block Bundle will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Documentation
- Expanded Supported Field Types documentation to include DateTime, Email, Country, Country Multiselect, Language, Language Multiselect, Gender, Slider, BooleanSelect
- Added documentation for simple relation field support: ManyToOneRelation, ManyToManyRelation, ManyToManyObjectRelation
- Updated API Reference tables with missing methods for ExtendedBlockContainer and ExtendedBlockItem
- Added comprehensive Localization Methods section to API Reference
- Added magic `__unset()` documentation
- Added configurable table prefix notes to database query examples
- Fixed hardcoded table name example in PHP_API.md

### Code Quality
- Applied PHP-CS-Fixer formatting fixes for consistent code style
- Updated admin translations with complete list of supported/unsupported field types

### Security
- Verified all SQL queries use parameterization
- Verified table/column names are properly quoted with quoteIdentifier()

## [1.0.0] - Initial Release

### Added
- ExtendedBlock data type with separate table storage
- SQL queryable block data
- Lazy loading support
- Pimcore-style UI/UX with inline controls
- Multiple block type support
- Nesting prevention validation
- Safe schema updates without data loss
- Per-item localized data storage
- Full admin UI integration

### Supported Field Types
- Text & Content: Input, Textarea, WYSIWYG, Email
- Numeric & Boolean: Numeric, Checkbox, Slider, BooleanSelect
- Selection: Select, Multiselect, Country, Country Multiselect, Language, Language Multiselect, Gender
- Date & Time: Date, DateTime
- Relations: ManyToOneRelation, ManyToManyRelation, ManyToManyObjectRelation
- Assets: Image, Link

### Restricted Field Types
- LocalizedFields (use ExtendedBlockItem's per-item localization instead)
- Block (nested container)
- FieldCollections (complex container)
- ObjectBricks (complex container)
- ExtendedBlock (self-nesting)
- Classificationstore (complex multi-table structure)
- AdvancedManyToManyRelation (complex metadata per relation)
- AdvancedManyToManyObjectRelation (complex metadata per relation)
- ReverseObjectRelation (virtual field)
