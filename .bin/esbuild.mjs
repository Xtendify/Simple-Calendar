import * as esbuild from 'esbuild';
import pkg from '../package.json' assert { type: 'json' };

const banner =
	`/*! ${pkg.title} - ${pkg.version}\n` +
	` * ${pkg.homepage}\n` +
	` * Copyright (c) Xtendify Technologies ${new Date().getFullYear()}\n` +
	` * Licensed GPLv2+` +
	` */\n`;

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
	entryPoints: [
		{
			in: 'assets/js/admin-add-calendar.js',
			out: 'js/admin-add-calendar.min',
		},
		{ in: 'assets/js/admin.js', out: 'js/admin.min' },
		{ in: 'assets/js/default-calendar.js', out: 'js/default-calendar.min' },
	],
	outdir: 'assets',
};

await esbuild.build(config);
