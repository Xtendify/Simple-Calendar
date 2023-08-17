import * as esbuild from 'esbuild';
import pkg from '../package.json' assert { type: 'json' };

const watch = process.argv.includes('--watch');

const banner =
	`/*! ${pkg.title} - ${pkg.version}\n` +
	` * ${pkg.homepage}\n` +
	` * Copyright (c) Xtendify Technologies ${new Date().getFullYear()}\n` +
	` * Licensed GPLv2+` +
	` */\n`;

const files = [
	{
		in: 'assets/js/admin-add-calendar.js',
		out: 'js/admin-add-calendar.min',
	},
	{ in: 'assets/js/admin.js', out: 'js/admin.min' },
	{ in: 'assets/js/default-calendar.js', out: 'js/default-calendar.min' },
	{ in: 'assets/css/admin-add-calendar.css', out: 'css/admin-add-calendar.min' },
	{ in: 'assets/css/admin-sett-style.css', out: 'css/admin-sett-style.min' },
	{ in: 'assets/css/admin.css', out: 'css/admin.min' },
	{ in: 'assets/css/default-calendar-grid.css', out: 'css/default-calendar-grid.min' },
	{ in: 'assets/css/default-calendar-list.css', out: 'css/default-calendar-list.min' },
	{ in: 'assets/css/sc-welcome-pg-style.css', out: 'css/sc-welcome-pg-style.min' },
	{ in: 'assets/css/tailwind-output-style.css', out: 'css/tailwind-output-style.min' },
];

const defaultConfig = {
	bundle: true,
	sourcemap: true,
	banner: {
		js: banner,
		css: banner,
	},
	minify: true,
};

const config = {
	...defaultConfig,
	entryPoints: files,
	loader: {
		'.png': 'dataurl',
		'.ttf': 'dataurl',
		'.woff': 'dataurl',
		'.eot': 'dataurl',
		'.svg': 'text',
	},
	outdir: 'assets',
};


if (watch) {
	const ctx = await esbuild.context(config);
	console.log('watching...')
	await ctx.watch();
} else {
	await esbuild.build(config);
}
