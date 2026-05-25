import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import path from "node:path";

const isStorybook = process.env.STORYBOOK === "true";

export default defineConfig({
  plugins: [
    ...(isStorybook
      ? []
      : [
          laravel({
            input: ["workbench/resources/css/app.css", "workbench/resources/js/app.tsx"],
            publicDirectory: "vendor/orchestra/testbench-core/laravel/public",
            buildDirectory: "build",
            refresh: ["workbench/resources/**", "workbench/routes/**", "resources/js/builder/**"],
          }),
        ]),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      "@builder": path.resolve(__dirname, "resources/js/builder"),
    },
  },
});
