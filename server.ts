import express from "express";
import path from "path";
import fs from "fs";
import { createServer as createViteServer } from "vite";

async function startServer() {
  const app = express();
  const PORT = 3000;

  app.use(express.json({ limit: "10mb" }));

  // API Route FIRST
  // این اندپوینت به صورت پوسته دایرکتوری تمامی فایل‌های افزونه ووکامرس کارت به کارت را اسکن کرده و به صورت JSON بازمی‌گرداند.
  app.get("/api/files", (req, res) => {
    try {
      const pluginDir = path.join(process.cwd(), "professional-card-to-card");
      if (!fs.existsSync(pluginDir)) {
        res.status(404).json({ error: "Plugin directory not found." });
        return;
      }

      const fileMap: Record<string, string> = {};

      const readFilesRecursively = (dir: string, baseDir: string) => {
        const items = fs.readdirSync(dir, { withFileTypes: true });
        for (const item of items) {
          const fullPath = path.join(dir, item.name);
          if (item.isDirectory()) {
            readFilesRecursively(fullPath, baseDir);
          } else if (item.isFile()) {
            const relPath = path.relative(baseDir, fullPath).replace(/\\/g, '/');
            // فایل ممکن است باینری باشد یا متنی، به طور پیش‌فرض متنی ذخیره می‌کنیم
            const content = fs.readFileSync(fullPath, "utf8");
            fileMap[relPath] = content;
          }
        }
      };

      readFilesRecursively(pluginDir, pluginDir);
      res.json({ files: fileMap });
    } catch (err: any) {
      console.error(err);
      res.status(500).json({ error: "Failed to read plugin directory: " + err.message });
    }
  });

  // Vite middleware for development
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), "dist");
    app.use(express.static(distPath));
    app.get("*", (req, res) => {
      res.sendFile(path.join(distPath, "index.html"));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
