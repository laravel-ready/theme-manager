require('laravel-mix-purgecss');

const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');
const fs = require('fs');
const path = require("path");

const publicJsFolder = 'public/js';

mix.js('resources/js/main.js', publicJsFolder).then(() => {
    mix.minify(`${publicJsFolder}/main.js`, `${publicJsFolder}/main.min.js`);
});

//#region Helpers

// const getThemeFolder = function(){
//     const _themeFolder = path.basename(path.resolve('./')),
//         _vendorFolder = path.basename(path.resolve('./../'));

//     themeFolder = `${_vendorFolder}/${_themeFolder}`;
// }

// getThemeFolder();

//#endregion


// const appPublicThemeFolder = path.resolve(`./../../../public/themes/${themeFolder}`);

// mix.copy('./public/js', `${appPublicThemeFolder}/js`);
// mix.copy('./public/css', `${appPublicThemeFolder}/css`);

// mix.copy('./resources/js', `${appPublicThemeFolder}/js`);
// mix.copy('./resources/css', `${appPublicThemeFolder}/css`);
// mix.copy('./resources/fonts', `${appPublicThemeFolder}/fonts`);
// mix.copy('./resources/images', `${appPublicThemeFolder}/images`);
