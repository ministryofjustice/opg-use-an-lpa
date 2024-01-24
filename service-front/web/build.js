import esbuild from 'esbuild';
import {default as fsWithCallbacks} from 'fs';

const fs = fsWithCallbacks.promises;

(async () => {
    const width = process.stdout.columns || 40;
    const hr = '\r\n'.padStart(width / 1.5, '-');

    let config = {
        entrypoints: ['./dist/index.js'],
        copy_files: {
            './src/robots.txt': 'robots.txt',
            './src/budget.json': 'budget.json',
            './src/images': 'assets/images',
            './node_modules/govuk-frontend/govuk/assets': 'assets',
            './node_modules/@ministryofjustice/frontend/moj/assets': 'assets',
        },
        out_dir: './dist',
    };

    console.log(
        `${hr}- Building with:\r\n\r\n${config.entrypoints.join('\r\n')}\r\n${hr}`,
    );

    //files to copy (uses experimental node cp)
    console.log(`- Copying files:\r\n`);
    for (const [file, file_dest] of Object.entries(config.copy_files)) {
        const destination = config.out_dir + '/' + file_dest;
        await fs.cp(file, destination, {recursive: true});
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
            target: ['es2015', 'ie11'],
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

    await esbuild
        .build({
            entryPoints: ['dist/pdf.css'],
            allowOverwrite: true,
            bundle: true,
            loader: {
                '.woff': 'base64',
                '.woff2': 'base64',
                '.ttf': 'base64',
                '.eot': 'base64',
                '.svg': 'base64',
                '.png': 'base64',
            },
            outfile: 'dist/stylesheets/pdf.css',
        })
        .catch(() => process.exit(1));

    console.log(hr);

    let text = await esbuild.analyzeMetafile(result.metafile);
    console.log(`- Analysis:\r\n${text}${hr}`);

    console.log(hr);
})();
