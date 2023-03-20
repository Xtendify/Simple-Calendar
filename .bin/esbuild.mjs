import * as esbuild from "esbuild";
import pkg from "../package.json" assert { type: "json" };

const defaultCalendarConfig = {
	entryPoints: ["assets/js/default-calendar.js"],
	bundle: true,
	outfile: "assets/js/default-calendar-bundled.js",
	sourcemap: true,
	banner: {
		js:
			`/*! ${pkg.title} - ${pkg.version}\n` +
			` * ${pkg.homepage}\n` +
			` * Copyright (c) Xtendify Technologies ${new Date().getFullYear()}\n` +
			` * Licensed GPLv2+` +
			` */\n`,
	},
};

const defaultCalendarMinifiedConfig =  {
	...defaultCalendarConfig,
	outfile: defaultCalendarConfig.outfile.replace(".js", ".min.js"),
	minify: true,
	sourcemap: true,
}



await esbuild.build(defaultCalendarConfig);
await esbuild.build(defaultCalendarMinifiedConfig);
