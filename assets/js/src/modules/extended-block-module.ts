/**
 * Extended Block Bundle - Dynamic Type Module
 *
 * This module registers the ExtendedBlock data type with
 * Pimcore Studio UI's dynamic type registry.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import { type AbstractModule, container, serviceIds } from '@pimcore/studio-ui-bundle'
import { type DynamicTypeObjectDataRegistry } from '@pimcore/studio-ui-bundle/modules/element'
import { DynamicTypeExtendedBlock } from '../dynamic-types/definitions/dynamic-type-extended-block'

export const ExtendedBlockModule: AbstractModule = {
  onInit: (): void => {
    // Get the object data registry
    const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
      serviceIds['DynamicTypes/ObjectDataRegistry']
    )

    // Register the ExtendedBlock dynamic type
    const extendedBlockType = container.get<DynamicTypeExtendedBlock>(
      'DynamicTypes/ObjectData/ExtendedBlock'
    )
    objectDataRegistry.registerDynamicType(extendedBlockType)
  }
}
