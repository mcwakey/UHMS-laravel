import { promises as fs } from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);

const projectRoot = process.cwd();
const docsDir = path.join(projectRoot, 'docs');
const markdownPath = path.join(docsDir, 'UHMS_USER_MANUAL.md');
const htmlPath = path.join(docsDir, 'UHMS_USER_MANUAL.print.html');
const pdfPath = path.join(docsDir, 'UHMS_USER_MANUAL.pdf');
const logPath = path.join(docsDir, 'UHMS_USER_MANUAL.export.log');

const browserCandidates = [
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
];

function escapeForInlineScript(value) {
  return JSON.stringify(value)
    .replace(/<\//g, '<\\/')
    .replace(/<!--/g, '<\\!--');
}

function toFileUrl(filePath) {
  return `file:///${filePath.replace(/\\/g, '/').replace(/ /g, '%20')}`;
}

async function findBrowser() {
  for (const browserPath of browserCandidates) {
    try {
      await fs.access(browserPath);
      return browserPath;
    } catch {
      // Try the next known path.
    }
  }

  throw new Error('Could not find Microsoft Edge or Google Chrome in the standard Windows install locations.');
}

function buildHtml(markdownSource) {
  const embeddedMarkdown = escapeForInlineScript(markdownSource);

  return `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UHMS User Manual</title>
    <style>
      :root {
        color-scheme: light;
        --ink: #17212b;
        --muted: #51606f;
        --panel: #ffffff;
        --line: #d9e1e8;
        --accent: #0e7490;
        --accent-soft: #e0f2fe;
        --page: #f3f6f9;
      }

      * {
        box-sizing: border-box;
      }

      html {
        background: var(--page);
      }

      body {
        margin: 0;
        font-family: "Segoe UI", Calibri, Arial, sans-serif;
        color: var(--ink);
        background:
          radial-gradient(circle at top left, rgba(14, 116, 144, 0.1), transparent 28%),
          radial-gradient(circle at top right, rgba(6, 95, 70, 0.08), transparent 24%),
          var(--page);
      }

      .page {
        width: min(1100px, calc(100vw - 32px));
        margin: 18px auto;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 20px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        padding: 28px 34px 36px;
      }

      h1,
      h2,
      h3,
      h4 {
        color: #0f172a;
        page-break-after: avoid;
        break-after: avoid-page;
      }

      h1 {
        font-size: 2.25rem;
        line-height: 1.1;
        margin: 0 0 0.65rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid var(--accent);
      }

      h2 {
        margin-top: 2rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--line);
        font-size: 1.6rem;
      }

      h3 {
        font-size: 1.18rem;
        margin-top: 1.45rem;
      }

      p,
      li,
      td,
      th {
        font-size: 0.96rem;
        line-height: 1.6;
      }

      p,
      ul,
      ol,
      table,
      pre,
      blockquote,
      .diagram-shell,
      img {
        break-inside: avoid-page;
        page-break-inside: avoid;
      }

      a {
        color: var(--accent);
        text-decoration: none;
      }

      img {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 14px auto 20px;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
      }

      table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0 22px;
        overflow: hidden;
        border-radius: 14px;
      }

      th,
      td {
        border: 1px solid var(--line);
        padding: 10px 12px;
        vertical-align: top;
      }

      th {
        background: #eef6fb;
        text-align: left;
      }

      code {
        font-family: Consolas, "Courier New", monospace;
        font-size: 0.9em;
        background: #f4f7fb;
        padding: 0.12rem 0.32rem;
        border-radius: 6px;
      }

      pre {
        background: #0f172a;
        color: #e2e8f0;
        padding: 14px 16px;
        border-radius: 14px;
        overflow: auto;
      }

      pre code {
        background: transparent;
        color: inherit;
        padding: 0;
      }

      blockquote {
        margin: 16px 0;
        padding: 10px 14px;
        border-left: 4px solid var(--accent);
        background: var(--accent-soft);
        color: var(--muted);
      }

      hr {
        border: 0;
        border-top: 1px solid var(--line);
        margin: 28px 0;
      }

      .diagram-shell {
        margin: 18px 0 24px;
        padding: 16px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: linear-gradient(180deg, #fbfdff 0%, #f5f9fc 100%);
      }

      .mermaid {
        display: flex;
        justify-content: center;
      }

      .render-error {
        border: 1px solid #fecaca;
        border-radius: 14px;
        background: #fff1f2;
        color: #9f1239;
        padding: 14px 16px;
        white-space: pre-wrap;
      }

      @page {
        size: A4;
        margin: 12mm;
      }

      @media print {
        html,
        body {
          background: #ffffff;
        }

        .page {
          width: auto;
          margin: 0;
          border: 0;
          border-radius: 0;
          box-shadow: none;
          padding: 0;
        }

        a {
          color: inherit;
        }
      }
    </style>
  </head>
  <body>
    <main class="page" id="app">Preparing PDF export...</main>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script type="module">
      import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';

      const markdownSource = ${embeddedMarkdown};

      function transformMermaidBlocks(root) {
        for (const codeBlock of root.querySelectorAll('pre > code.language-mermaid, pre > code.mermaid')) {
          const pre = codeBlock.closest('pre');
          const shell = document.createElement('div');
          shell.className = 'diagram-shell';

          const host = document.createElement('div');
          host.className = 'mermaid';
          host.textContent = codeBlock.textContent;

          shell.appendChild(host);
          pre.replaceWith(shell);
        }
      }

      function waitForImages() {
        const pendingImages = [...document.images].filter((image) => !image.complete);

        return Promise.all(
          pendingImages.map(
            (image) =>
              new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
              })
          )
        );
      }

      async function render() {
        mermaid.initialize({
          startOnLoad: false,
          securityLevel: 'loose',
          theme: 'default',
        });

        marked.setOptions({
          gfm: true,
          breaks: false,
          headerIds: false,
          mangle: false,
        });

        const app = document.getElementById('app');
        app.innerHTML = marked.parse(markdownSource);
        transformMermaidBlocks(app);
        await mermaid.run({ querySelector: '.mermaid' });
        await waitForImages();

        document.title = 'UHMS User Manual';
        document.documentElement.dataset.rendered = 'true';
      }

      render().catch((error) => {
        const app = document.getElementById('app');
        app.innerHTML = '';

        const message = document.createElement('div');
        message.className = 'render-error';
        message.textContent = error?.stack || String(error);
        app.appendChild(message);

        document.documentElement.dataset.rendered = 'error';
      });
    </script>
  </body>
</html>`;
}

async function exportPdf() {
  const markdownSource = await fs.readFile(markdownPath, 'utf8');
  const browserPath = await findBrowser();
  const userDataDir = await fs.mkdtemp(path.join(os.tmpdir(), 'uhms-user-manual-'));

  await fs.writeFile(htmlPath, buildHtml(markdownSource), 'utf8');

  const fileUrl = toFileUrl(htmlPath);
  const args = [
    `--user-data-dir=${userDataDir}`,
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--allow-file-access-from-files',
    '--run-all-compositor-stages-before-draw',
    '--virtual-time-budget=20000',
    '--no-pdf-header-footer',
    `--print-to-pdf=${pdfPath}`,
    fileUrl,
  ];

  try {
    try {
      await execFileAsync(browserPath, args, {
        windowsHide: true,
        maxBuffer: 10 * 1024 * 1024,
      });
    } catch (error) {
      if (error?.stderr?.includes('headless=new')) {
        const fallbackArgs = args.filter((arg) => arg !== '--headless=new');
        fallbackArgs.splice(1, 0, '--headless');
        await execFileAsync(browserPath, fallbackArgs, {
          windowsHide: true,
          maxBuffer: 10 * 1024 * 1024,
        });
      } else {
        throw error;
      }
    }

    await fs.rm(userDataDir, { recursive: true, force: true });
  } catch (error) {
    const details = [error?.message || 'Unknown export error', error?.stdout || '', error?.stderr || '']
      .filter(Boolean)
      .join('\n\n');

    await fs.writeFile(logPath, details, 'utf8');
    await fs.rm(userDataDir, { recursive: true, force: true });
    throw error;
  }

  const pdfStats = await fs.stat(pdfPath);
  if (pdfStats.size < 32 * 1024) {
    throw new Error(`PDF export finished but the output file looks too small (${pdfStats.size} bytes).`);
  }

  console.log(`HTML export written to ${htmlPath}`);
  console.log(`PDF export written to ${pdfPath}`);
}

exportPdf().catch((error) => {
  console.error(error?.stack || String(error));
  process.exitCode = 1;
});