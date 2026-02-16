/**
 * Extended Block Bundle - Content Component
 *
 * This component renders the content area of the ExtendedBlock,
 * including the list of block items and add buttons.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import React, { useMemo, useRef } from 'react'
import { type ExtendedBlockProps } from './extended-block'
import { ExtendedBlockItem } from './extended-block-item'
import { BaseView } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/layout-related/views'
import { useNumberedList } from '@pimcore/studio-ui-bundle/components/form/controls/numbered-list/provider/numbered-list'
import { BlockAddButton } from '@pimcore/studio-ui-bundle/components/block'
import { Space } from '@pimcore/studio-ui-bundle/components/space'
import { Box } from '@pimcore/studio-ui-bundle/components/box'
import { FieldLabel } from '@pimcore/studio-ui-bundle/modules/element/dynamic-types/definitions/objects/data-related/helpers/label'

/**
 * ExtendedBlockContent Component
 *
 * Renders the block items list with controls for adding, removing,
 * and reordering items.
 */
export const ExtendedBlockContent: React.FC<ExtendedBlockProps> = (props) => {
  const { values } = useNumberedList()
  const keyCounterRef = useRef(0)

  const maxItemsCount = props?.maxItems ?? 0
  const valuesKeys = Object.keys(values)
  const isNoteditable = props.noteditable === true
  const isDisallowAddRemove = props.disallowAddRemove === true

  const isItemLimitReached = maxItemsCount > 0 && valuesKeys.length >= maxItemsCount
  const isHideAddButton = isNoteditable || isItemLimitReached || valuesKeys.length > 0 || isDisallowAddRemove

  // Generate stable keys for items to fix deletion/reordering issues
  const stableKeys = useMemo(() => {
    return values.map(() => `extended-block-item-${++keyCounterRef.current}`)
  }, [values.length])

  return useMemo(() => (
    <BaseView
      border={ props.border }
      collapsed={ props.collapsed }
      collapsible={ props.collapsible }
      contentPadding='none'
      extra={ !isHideAddButton && <BlockAddButton /> }
      extraPosition='start'
      theme='default'
      title={
        <FieldLabel
          label={ props.title }
          name={ props.name }
        />
      }
    >
      <Box padding={ { top: 'extra-small' } }>
        <Space
          className='w-full'
          direction='vertical'
          size='extra-small'
        >
          {values.map((_value, index) => (
            <div key={ stableKeys[index] ?? `extended-block-item-${index}` }>
              <ExtendedBlockItem
                disallowAdd={ isDisallowAddRemove || isItemLimitReached || isNoteditable }
                disallowDelete={ isDisallowAddRemove || isNoteditable }
                disallowReorder={ props.disallowReorder === true || isNoteditable }
                field={ index }
                name={ props.name }
                noteditable={ props.noteditable }
                styleElement={ props.styleElement }
              >
                {props.children}
              </ExtendedBlockItem>
            </div>
          ))}
        </Space>
      </Box>
    </BaseView>
  ), [values, props, isNoteditable, isDisallowAddRemove, isItemLimitReached, isHideAddButton, stableKeys])
}
