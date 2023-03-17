import * as esbuild from "esbuild";
import pkg from "../package.json" assert { type: "json" };

await esbuild.build({
	entryPoints: ["assets/js/default-calendar.js"],
	bundle: true,
	outfile: "assets/js/default-calendar.min.js",
	minify: true,
	sourcemap: true,
	banner: {
		js:
			`/*! ${pkg.title} - ${pkg.version}\n` +
			` * ${pkg.homepage}\n` +
			` * Copyright (c) Xtendify Technologies ${new Date().getFullYear()}\n` +
			` * Licensed GPLv2+` +
			` */\n`,
	},
});
