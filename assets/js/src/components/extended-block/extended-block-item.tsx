/**
 * Extended Block Bundle - Item Component
 *
 * This component renders a single ExtendedBlock item with controls
 * for adding, removing, and reordering.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import React from 'react'
import { type AbstractObjectDataDefinition } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related'
import { type AbstractObjectLayoutDefinition } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/layout-related'
import { BlockItemView } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related/components/block'
import { FormContext } from '@pimcore/studio-ui-bundle/modules/data-object/editor/types/object/tab-manager/tabs/edit/providers/form-context'

/**
 * Props for ExtendedBlockItem component
 */
export interface ExtendedBlockItemProps {
  /** Child layout or data definitions */
  children?: AbstractObjectLayoutDefinition | AbstractObjectDataDefinition | Array<AbstractObjectLayoutDefinition | AbstractObjectDataDefinition>
  /** Index of this item in the list */
  field: number
  /** Field name */
  name?: string
  /** Whether adding is disabled */
  disallowAdd?: boolean
  /** Whether deletion is disabled */
  disallowDelete?: boolean
  /** Whether reordering is disabled */
  disallowReorder?: boolean
  /** Whether the field is not editable */
  noteditable?: boolean | null
  /** Custom CSS style for the element */
  styleElement?: string
}

/**
 * ExtendedBlockItem Component
 *
 * Renders a single block item with:
 * - Add before/after buttons
 * - Delete button
 * - Move up/down buttons
 * - Child field content
 */
export const ExtendedBlockItem: React.FC<ExtendedBlockItemProps> = (props) => {
  const {
    children,
    field,
    name,
    disallowAdd,
    disallowDelete,
    disallowReorder,
    noteditable,
    styleElement
  } = props

  return (
    <FormContext.Provider value={ { index: field } }>
      <BlockItemView
        disallowAdd={ disallowAdd }
        disallowDelete={ disallowDelete }
        disallowReorder={ disallowReorder }
        field={ field }
        name={ name }
        noteditable={ noteditable }
        style={ styleElement ? { cssText: styleElement } : undefined }
      >
        {children}
      </BlockItemView>
    </FormContext.Provider>
  )
}
