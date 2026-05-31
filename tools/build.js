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

const jsVendorProfiles = {
    jquery3: {
        jquery: 'jquery',
        migrate: 'jquery-migrate3'
    },
    jquery4: {
        jquery: 'jquery4',
        migrate: 'jquery-migrate'
    }
};

const jqueryUiFiles = [
    'version.js',
    'keycode.js',
    'jquery-patch.js',
    'widget.js',
    'widgets/mouse.js',
    'widgets/datepicker.js',
    'widgets/slider.js',
    'jquery-var-for-color.js',
    'vendor/jquery-color/jquery.color.js',
    'effect.js',
    'effects/effect-highlight.js'
];

function packageFile(packageName, file)
{
    return path.join(__dirname, 'node_modules', packageName, file);
}

function copyFile(from, to)
{
    console.log(chalk.green('copy ' + path.relative(__dirname, from) + ' -> ' + path.relative(srcDir, to)));
    fs.mkdirSync(path.dirname(to), {recursive: true});
    fs.copyFileSync(from, to);
}

function syncJqueryUi()
{
    const banner = '/*! jQuery UI custom build for Typecho: core, widget, mouse, datepicker, slider, highlight effect. */\n';
    const code = jqueryUiFiles.map(function (file) {
        return fs.readFileSync(packageFile('jquery-ui', path.join('ui', file)), 'utf8');
    }).join('\n\n');

    const outFile = path.join(srcDir, 'js/jquery-ui.js');
    console.log(chalk.green('bundle jquery-ui.js'));
    fs.mkdirSync(path.dirname(outFile), {recursive: true});
    fs.writeFileSync(outFile, banner + code);
}

function syncVendorJs(profileName = 'jquery3')
{
    const profile = jsVendorProfiles[profileName];

    if (!profile) {
        throw new Error('Unknown JS vendor profile: ' + profileName);
    }

    console.log(chalk.blue('sync vendor js (' + profileName + ')'));
    copyFile(packageFile(profile.jquery, 'dist/jquery.js'), path.join(srcDir, 'js/jquery.js'));
    copyFile(packageFile(profile.migrate, 'dist/jquery-migrate.js'), path.join(srcDir, 'js/jquery-migrate.js'));
    copyFile(packageFile('jquery-ui-timepicker-addon', 'dist/jquery-ui-timepicker-addon.js'), path.join(srcDir, 'js/timepicker.js'));
    copyFile(packageFile('dompurify', 'dist/purify.js'), path.join(srcDir, 'js/purify.js'));
    syncJqueryUi();
}

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
    } else if (action === 'vendor_js') {
        syncVendorJs();
    } else if (action === 'js' || action === 'js_jquery4') {
        console.log(chalk.blue('build js'));
        syncVendorJs(action === 'js_jquery4' ? 'jquery4' : 'jquery3');

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
