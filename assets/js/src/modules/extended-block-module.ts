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

import { type AbstractModule, container } from '@pimcore/studio-ui-bundle'
import { serviceIds } from '@pimcore/studio-ui-bundle/app'
import { type DynamicTypeObjectDataRegistry } from '@pimcore/studio-ui-bundle/modules/element'

export const ExtendedBlockModule: AbstractModule = {
  onInit: (): void => {
    // Get the object data registry from the DI container
    const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
      serviceIds['DynamicTypes/ObjectDataRegistry']
    )

    // Register the ExtendedBlock dynamic type
    // The type was bound in onInit of the plugin
    objectDataRegistry.registerDynamicType(
      container.get('DynamicTypes/ObjectData/ExtendedBlock')
    )
  }
}
