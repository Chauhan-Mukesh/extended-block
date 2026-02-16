/**
 * Extended Block Bundle - Dynamic Type Definition
 *
 * This class defines the ExtendedBlock data type for Pimcore Studio UI.
 * It extends from the abstract object data type and provides custom
 * components for editing and displaying ExtendedBlock data.
 *
 * Unlike the standard Block which stores data as serialized JSON,
 * ExtendedBlock stores data in separate database tables for better
 * performance and SQL queryability.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import { type ReactElement } from 'react'
import { injectable } from '@pimcore/studio-ui-bundle/app'
import {
  DynamicTypeObjectDataAbstract,
  type AbstractObjectDataDefinition,
  type GetGridCellDefinitionProps,
  Block
} from '@pimcore/studio-ui-bundle/modules/element'
import { type FormItemProps } from 'antd'

/**
 * Extended Block data type definition for Pimcore Studio UI.
 *
 * This dynamic type provides:
 * - Edit component for object editing (reuses Block component)
 * - Grid cell preview showing item count
 * - Version view component for comparing versions
 * - Edit modal settings for grid inline editing
 */
@injectable()
export class DynamicTypeExtendedBlock extends DynamicTypeObjectDataAbstract {
  /**
   * Unique identifier for this data type.
   * Must match the PHP data type identifier.
   */
  readonly id: string = 'extendedBlock'

  /**
   * Returns the main editing component for ExtendedBlock.
   * Reuses the Block component from Pimcore Studio UI since ExtendedBlock
   * has the same UI behavior as Block (only storage differs).
   *
   * @param props - Component properties from the data object editor
   * @returns The Block React component
   */
  getObjectDataComponent (props: AbstractObjectDataDefinition): ReactElement<AbstractObjectDataDefinition> {
    return (
      <Block { ...props } />
    )
  }

  /**
   * Returns form item properties for the data component.
   * Removes the label since ExtendedBlock has its own title in the panel header.
   *
   * @param props - Component properties
   * @returns Form item properties
   */
  getObjectDataFormItemProps (props: AbstractObjectDataDefinition): FormItemProps {
    return {
      ...super.getObjectDataFormItemProps(props),
      label: null
    }
  }

  /**
   * Returns the version view component for comparing object versions.
   * Uses the same Block component with noteditable set to true.
   *
   * @param props - Component properties
   * @returns The Block React component in read-only mode
   */
  getVersionObjectDataComponent (props: AbstractObjectDataDefinition): ReactElement<AbstractObjectDataDefinition> {
    return (
      <Block
        { ...props }
        noteditable
      />
    )
  }

  /**
   * Returns the grid cell preview component showing the item count.
   *
   * @param props - Grid cell properties
   * @returns The preview React element
   */
  getGridCellPreviewComponent (props: GetGridCellDefinitionProps): ReactElement {
    const rawValue = props.cellProps.getValue()
    const value = Array.isArray(rawValue) ? rawValue : null
    const count = value?.length ?? 0

    return (
      <span>{ count } item{ count !== 1 ? 's' : '' }</span>
    )
  }
}
