import esbuild from 'esbuild';
import { default as fsWithCallbacks } from 'fs';
const fs = fsWithCallbacks.promises;

(async () => {
  const width = process.stdout.columns || 40;
  const hr = '\r\n'.padStart(width / 1.5, '-');

  let config = {
    entrypoints: ['./web/assets/main.js'],
    copy_files: {
      './node_modules/govuk-frontend/govuk/assets': 'web/static/assets',
      './node_modules/@ministryofjustice/frontend/moj/assets': 'web/static/assets',
    },
    out_dir: './web/static/javascript',
  };

  console.log(
    `${hr}- Building with:\r\n\r\n${config.entrypoints.join('\r\n')}\r\n${hr}`,
  );

  //files to copy (uses experimental node cp)
  console.log(`- Copying files:\r\n`);
  for (const [file, file_dest] of Object.entries(config.copy_files)) {
    const destination = file_dest;
    await fs.cp(file, destination, { recursive: true });
    console.log(`Copied file from ${file} file to ${destination}`);
  }

  let result = await esbuild
    .build({
      entryPoints: config.entrypoints,
      bundle: true,
      allowOverwrite: true,
      outdir: config.out_dir,
      minify: true,
      plugins: [],
      sourcemap: true,
      target: ['es6'],
      platform: 'browser',
      metafile: true,
      supported: {
        arrow: false,
      },
    })
    .catch((e) => {
      console.log(e);
      process.exit(1);
    });

  console.log(hr);

  let text = await esbuild.analyzeMetafile(result.metafile);
  console.log(`- Analysis:\r\n${text}${hr}`);

  console.log(hr);
})();
