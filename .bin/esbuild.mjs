import * as esbuild from 'esbuild';
import pkg from '../package.json' assert { type: 'json' };

const banner =
	`/*! ${pkg.title} - ${pkg.version}\n` +
	` * ${pkg.homepage}\n` +
	` * Copyright (c) Xtendify Technologies ${new Date().getFullYear()}\n` +
	` * Licensed GPLv2+` +
	` */\n`;

const commonOptions = {
	bundle: true,
	sourcemap: true,
	banner: {
		js: banner,
		css: banner,
	},
	outExtension: { '.css': '.min.css', '.js': '.min.js' },
	loader: {
		'.svg': 'file',
		'.ttf': 'file',
		'.woff': 'file',
		'.png': 'file',
		'.eot': 'file',
	},
};

const cssConfig = {
	...commonOptions,
	entryPoints: [
		'assets/css/admin-add-calendar.css',
		'assets/css/admin.css',
		'assets/css/default-calendar-list.css',
		'assets/css/default-calendar-grid.css',
	],
	outdir: 'assets/min',
	minify: true,
};

await esbuild.build(cssConfig);

const jsConfig = {
	...commonOptions,
	entryPoints: ['assets/js/admin-add-calendar.js', 'assets/js/admin.js', 'assets/js/default-calendar.js'],
	outdir: 'assets/min',
	minify: true,
};

await esbuild.build(jsConfig);
