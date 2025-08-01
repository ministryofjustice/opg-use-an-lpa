import * as esbuild from 'esbuild';
import externalGlobalPkg from 'esbuild-plugin-external-global';

const { externalGlobalPlugin } = externalGlobalPkg;

await esbuild.build({
  entryPoints: ['src/index.ts'],
  target: 'es2022',
  bundle: true,
  outfile: 'mock-responses.js',
  plugins: [
    externalGlobalPlugin({
      '@imposter-js/types': '__imposter_types',
    })
  ],
})
