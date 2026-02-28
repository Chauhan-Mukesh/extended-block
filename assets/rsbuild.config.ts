import { defineConfig } from '@rsbuild/core'
import { pluginReact } from '@rsbuild/plugin-react'
import { pluginModuleFederation } from '@module-federation/rsbuild-plugin'
import { pluginGenerateEntrypoints } from '@pimcore/studio-ui-bundle/rsbuild/plugins'
import { createDynamicRemote } from '@pimcore/studio-ui-bundle/rsbuild/utils'
import path from 'path'
import fs from 'fs'
import { v4 } from 'uuid'
import packages from './package.json'

const buildId = v4()
const buildPath = path.resolve(__dirname, '..', 'public', 'studio-ui-build', buildId)

// Clean up old builds
if (fs.existsSync(path.resolve(__dirname, '..', 'public', 'studio-ui-build'))) {
  fs.readdirSync(path.resolve(__dirname, '..', 'public', 'studio-ui-build')).forEach((file) => {
    fs.rmSync(path.resolve(__dirname, '..', 'public', 'studio-ui-build', file), { recursive: true })
  })
}

if (!fs.existsSync(buildPath)) {
  fs.mkdirSync(buildPath, { recursive: true })
}

const nodeEnv = process.env.NODE_ENV
let env: 'development' | 'production' = 'production'

const isDevServer = nodeEnv === 'dev-server'
if (nodeEnv !== env) {
  env = 'development'
}

export default defineConfig({
  mode: env,
  server: {
    port: 3033,
  },
  dev: {
    ...(!isDevServer ? { assetPrefix: '/bundles/extendedblock/studio-ui-build/' + buildId } : {}),
    client: {
      host: 'localhost',
      port: 3033,
      protocol: 'ws'
    }
  },
  source: {
    entry: {
      main: './js/src/main.ts'
    },
    decorators: {
      version: 'legacy'
    }
  },
  output: {
    manifest: true,
    assetPrefix: '/bundles/extendedblock/studio-ui-build/' + buildId,
    distPath: {
      root: buildPath
    },
  },
  tools: {
    bundlerChain: (chain) => {
      chain.output.uniqueName('extended_block_bundle')
    },
  },
  plugins: [
    pluginGenerateEntrypoints(),
    pluginReact(),
    pluginModuleFederation({
      name: 'extended_block_bundle',
      filename: 'static/js/remoteEntry.js',
      exposes: {
        '.': './js/src/plugins.ts',
      },
      dts: false,
      remotes: {
        '@pimcore/studio-ui-bundle': createDynamicRemote('pimcore_studio_ui_bundle'),
      },
      shared: {
        ...packages.dependencies,
        react: {
          singleton: true,
          eager: true,
          requiredVersion: false,
        },
        'react-dom': {
          singleton: true,
          eager: true,
          requiredVersion: false,
        }
      },
    })
  ]
})
