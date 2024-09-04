import * as esbuild from 'esbuild';
import globals from 'esbuild-plugin-globals';

await esbuild.build({
  entryPoints: ['src/index.mjs'],
  target: 'es2022',
  bundle: true,
  outfile: 'mock-responses.js',
  plugins: [
    globals({
      './types/index': '__imposter_types'
    })
  ]
})
