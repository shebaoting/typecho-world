import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import chalk from 'chalk';
import * as sass from 'sass';
import { minify } from 'terser';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const srcDir = path.resolve(__dirname, '../admin/src');
const distDir = path.resolve(__dirname, '../admin');
const themeDir = path.resolve(__dirname, '../usr/themes/classic-22');
const action = process.argv.at(-1);

function buildSass(file, dist, sassDir)
{
    const outFile = path.join(dist, file.split('.')[0] + '.css');
    console.log(chalk.green('processing ' + file));

    const result = sass.compile(path.join(sassDir, file), {
        loadPaths: [sassDir],
        style: 'compressed',
        quietDeps: true,
        silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'slash-div', 'if-function']
    });

    fs.mkdirSync(path.dirname(outFile), {recursive: true});
    fs.writeFileSync(outFile, result.css);
}

async function minifyJs(file, dist)
{
    console.log(chalk.green('minify ' + file));
    const code = {};
    const outFile = path.join(dist, file);
    code[file] = fs.readFileSync(path.join(srcDir, 'js', file), 'utf8');
    const result = await minify(code, {
        format: {
            comments: /^!|@preserve|@license|@cc_on/i
        }
    });

    fs.mkdirSync(path.dirname(outFile), {recursive: true});
    fs.writeFileSync(outFile, result.code);
}

function listFiles(dir, regExp)
{
    if (!fs.existsSync(dir)) {
        return [];
    }

    let files = fs.readdirSync(dir), result = [];

    files.map(function (file) {
        if (file.match(regExp)) {
            result.push(file);
        }
    });

    return result;
}

async function main()
{
    if (action === 'css') {
        console.log(chalk.blue('build css'));

        listFiles(path.join(srcDir, 'scss'), /^[a-z0-9-]+\.scss$/).forEach(function (file) {
            buildSass(file, path.join(distDir, 'css'), path.join(srcDir, 'scss'));
        });
    } else if (action === 'js') {
        console.log(chalk.blue('build js'));

        for (const file of listFiles(path.join(srcDir, 'js'), /^[-\w]+\.js$/)) {
            await minifyJs(file, path.join(distDir, 'js'));
        }
    } else if (action === 'theme_css') {
        console.log(chalk.blue('build theme css'));

        listFiles(path.join(themeDir, 'static/scss'), /^[a-z0-9-]+\.scss$/).forEach(function (file) {
            buildSass(file, path.join(themeDir, 'static/css'), path.join(themeDir, 'static/scss'));
        });
    } else {
        console.log(chalk.red('Please choose correct action.'));
        process.exitCode = 1;
    }
}

main().catch(function (error) {
    console.error(chalk.red(error.stack || error.message));
    process.exitCode = 1;
});
