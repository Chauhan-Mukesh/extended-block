/**
 * Extended Block Bundle - Main Component
 *
 * This component renders the ExtendedBlock editor in Pimcore Studio UI.
 * It follows the same pattern as the standard Block component but
 * manages data that is stored in separate database tables.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import React from 'react'
import { type AbstractObjectDataDefinition } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related'
import { type AbstractObjectLayoutDefinition } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/layout-related'
import { ExtendedBlockContent } from './extended-block-content'
import { Form } from '@pimcore/studio-ui-bundle/components'

/**
 * Props for the ExtendedBlock component
 */
export interface ExtendedBlockProps extends AbstractObjectDataDefinition {
  /** Child layout or data definitions */
  children?: AbstractObjectLayoutDefinition | AbstractObjectDataDefinition | Array<AbstractObjectLayoutDefinition | AbstractObjectDataDefinition>
  /** Whether the block is collapsed by default */
  collapsed?: boolean
  /** Whether the block is collapsible */
  collapsible?: boolean
  /** Whether reordering items is disabled */
  disallowReorder?: boolean
  /** Whether adding/removing items is disabled */
  disallowAddRemove?: boolean
  /** Maximum number of items allowed */
  maxItems?: number
  /** Minimum number of items required */
  minItems?: number
  /** Whether the field is inherited */
  inherited?: boolean
  /** Whether lazy loading is enabled */
  lazyLoading?: boolean
  /** Custom CSS style for block elements */
  styleElement?: string
  /** Change handler */
  onChange?: (value: unknown) => void
  /** Current value */
  value?: unknown
}

/**
 * ExtendedBlock Component
 *
 * Main entry point for the ExtendedBlock editor. Uses Form.NumberedList
 * for managing the list of block items, similar to the standard Block.
 */
export const ExtendedBlock: React.FC<ExtendedBlockProps> = (props) => {
  return (
    <Form.NumberedList
      onChange={ props.onChange }
      value={ props.value }
    >
      <ExtendedBlockContent { ...props } />
    </Form.NumberedList>
  )
}
