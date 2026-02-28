/**
 * Extended Block Bundle - Dynamic Type Definition
 *
 * This class defines the ExtendedBlock data type for Pimcore Studio UI.
 * It extends from DynamicTypeObjectDataBlock to inherit all Block behavior
 * since ExtendedBlock has identical UI - only the storage mechanism differs.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import { DynamicTypeObjectDataBlock } from '@pimcore/studio-ui-bundle/modules/element'

/**
 * ExtendedBlock dynamic type for Pimcore Studio UI.
 *
 * Extends from DynamicTypeObjectDataBlock to reuse all Block UI components
 * and behavior. The only difference is the data type identifier which maps
 * to the PHP ExtendedBlock class that stores data in separate tables.
 *
 * Features inherited from Block:
 * - Edit component with add/remove/reorder items
 * - Grid cell preview showing item count
 * - Version view component
 * - Edit modal settings for grid inline editing
 */
export class DynamicTypeExtendedBlock extends DynamicTypeObjectDataBlock {
  /**
   * Unique identifier for this data type.
   * Must match the PHP data type identifier 'extendedBlock'.
   */
  readonly id: string = 'extendedBlock'
}
