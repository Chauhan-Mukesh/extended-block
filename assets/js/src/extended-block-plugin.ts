/**
 * Extended Block Bundle - Pimcore Studio UI Plugin
 *
 * This plugin registers the ExtendedBlock data type for Pimcore Studio UI.
 * ExtendedBlock stores data in separate database tables for better
 * performance and SQL queryability.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import { type IAbstractPlugin } from '@pimcore/studio-ui-bundle'
import { DynamicTypeExtendedBlock } from './dynamic-types/definitions/dynamic-type-extended-block'
import { ExtendedBlockModule } from './modules/extended-block-module'

export const ExtendedBlockPlugin: IAbstractPlugin = {
  name: 'ExtendedBlockPlugin',

  onInit ({ container }) {
    // Register the ExtendedBlock dynamic type in the DI container
    container.bind('DynamicTypes/ObjectData/ExtendedBlock').to(DynamicTypeExtendedBlock)
  },

  onStartup ({ moduleSystem }) {
    // Register the module that extends the dynamic type registry
    moduleSystem.registerModule(ExtendedBlockModule)
  }
}
