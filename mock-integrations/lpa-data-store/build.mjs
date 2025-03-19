import * as esbuild from 'esbuild';

await esbuild.build({
  entryPoints: ['src/index.mjs'],
  target: 'es2022',
  bundle: true,
  outfile: 'mock-responses.js'
})
