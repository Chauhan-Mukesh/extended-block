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

import React from 'react'
import { injectable } from 'inversify'
import {
  DynamicTypeObjectDataAbstract,
  type AbstractObjectDataDefinition,
  type EditMode,
  type EditModalSettings,
  type GetGridCellDefinitionProps
} from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related'
import { ExtendedBlock } from '../../components/extended-block/extended-block'
import { ExtendedBlockVersionView } from '../../components/extended-block/extended-block-version-view'
import { ItemsCount } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/grid-cell-preview'
import { type FormItemProps } from 'antd'

/**
 * Extended Block data type definition for Pimcore Studio UI.
 *
 * This dynamic type provides:
 * - Edit component for object editing
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
   * Grid cell edit mode - uses modal for better UX
   */
  gridCellEditMode: EditMode = 'edit-modal'

  /**
   * Modal settings for grid cell editing
   */
  gridCellEditModalSettings: EditModalSettings = {
    modalSize: 'XL',
    formLayout: 'vertical'
  }

  /**
   * Returns the main editing component for ExtendedBlock.
   *
   * @param props - Component properties from the data object editor
   * @returns The ExtendedBlock React component
   */
  getObjectDataComponent (props: AbstractObjectDataDefinition): React.ReactElement<AbstractObjectDataDefinition> {
    return (
      <ExtendedBlock { ...props } />
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
   *
   * @param props - Component properties
   * @returns The version view React component
   */
  getVersionObjectDataComponent (props: AbstractObjectDataDefinition): React.ReactElement<AbstractObjectDataDefinition> {
    return (
      <ExtendedBlockVersionView { ...props } />
    )
  }

  /**
   * Returns the grid cell preview component showing the item count.
   *
   * @param props - Grid cell properties
   * @returns The preview React component
   */
  getGridCellPreviewComponent (props: GetGridCellDefinitionProps): React.ReactElement {
    const value: [] | null = props.cellProps.getValue()

    return (
      <ItemsCount count={ value?.length ?? 0 } />
    )
  }
}
