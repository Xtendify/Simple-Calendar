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
		out: 'admin-add-calendar.min',
	},
	{ in: 'assets/js/admin.js', out: 'admin.min' },
	{ in: 'assets/js/default-calendar.js', out: 'default-calendar.min' },
	{ in: 'assets/css/admin-add-calendar.css', out: 'admin-add-calendar.min' },
	{ in: 'assets/css/admin-sett-style.css', out: 'admin-sett-style.min' },
	{ in: 'assets/css/admin.css', out: 'admin.min' },
	{ in: 'assets/css/default-calendar-grid.css', out: 'default-calendar-grid.min' },
	{ in: 'assets/css/default-calendar-list.css', out: 'default-calendar-list.min' },
	{ in: 'assets/css/sc-welcome-pg-style.css', out: 'sc-welcome-pg-style.min' },
	{ in: 'assets/generated/tailwind-output.css', out: 'tailwind.min' },
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
	outdir: 'assets/generated',
};

if (watch) {
	const ctx = await esbuild.context(config);
	console.log('watching...');
	await ctx.watch();
} else {
	await esbuild.build(config);
}
