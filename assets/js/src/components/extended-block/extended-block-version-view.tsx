/**
 * Extended Block Bundle - Version View Component
 *
 * This component renders the ExtendedBlock in version comparison view,
 * showing the block items in read-only mode for comparing versions.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import React from 'react'
import { type AbstractObjectDataDefinition } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related'
import { ExtendedBlock, type ExtendedBlockProps } from './extended-block'

/**
 * ExtendedBlockVersionView Component
 *
 * Renders the ExtendedBlock in version view mode.
 * All fields are displayed as read-only (noteditable: true).
 */
export const ExtendedBlockVersionView: React.FC<AbstractObjectDataDefinition> = (props) => {
  const versionProps: ExtendedBlockProps = {
    ...props,
    noteditable: true,
    disallowAddRemove: true,
    disallowReorder: true
  }

  return (
    <ExtendedBlock { ...versionProps } />
  )
}
