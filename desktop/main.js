'use strict';

const { app, BrowserWindow, dialog, screen } = require('electron');
const { spawn } = require('child_process');
const path = require('path');
const http = require('http');
const os = require('os');
const fs = require('fs');

// ──────────────────────────────────────────────
// Windows-specific flags (must be set BEFORE app.ready)
// Fix DPI scaling so the UI is not blurry on HiDPI Windows displays
// ──────────────────────────────────────────────
if (process.platform === 'win32') {
  // Tell Windows we are DPI-aware; removes the blurry-scaling issue
  app.commandLine.appendSwitch('high-dpi-support', '1');
  app.commandLine.appendSwitch('force-device-scale-factor', '1');
  // Smooth font rendering on Windows (matches macOS quality)
  app.commandLine.appendSwitch('disable-lcd-text');
  // Disable GPU on very old Windows hardware to prevent blank screens
  // app.disableHardwareAcceleration(); // uncomment if users see blank windows
}

// ──────────────────────────────────────────────
// State
// ──────────────────────────────────────────────
let mainWindow   = null;
let phpServerProcess = null;
let getPort;   // loaded lazily (ESM → dynamic import)

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

/**
 * Returns the platform string used in /bin/<platform>/
 */
function getPlatform() {
  return process.platform === 'win32' ? 'win' : 'mac';
}

/**
 * Returns the PHP executable path, bundled or system fallback.
 */
function getPhpBin() {
  const platform  = getPlatform();
  const phpExec   = platform === 'win' ? 'php.exe' : 'php';

  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'bin', platform, phpExec);
  }

  // Development: use system PHP
  return process.platform === 'win32' ? 'php.exe' : 'php';
}

/**
 * Returns the PHP document root (the CodeIgniter /public folder).
 */
function getDocRoot() {
  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'www', 'public');
  }
  return path.join(__dirname, '..', 'public');
}

// ──────────────────────────────────────────────
// PHP Server
// ──────────────────────────────────────────────

async function startPhpServer(port) {
  const phpBin  = getPhpBin();
  const docRoot = getDocRoot();
  const cwd     = path.dirname(docRoot);   // one level above /public

  // Make sure the PHP binary actually exists (packaged mode)
  if (app.isPackaged && !fs.existsSync(phpBin)) {
    dialog.showErrorBox(
      'PHP Not Found',
      `Could not locate the bundled PHP binary:\n${phpBin}\n\nPlease reinstall Voxxera POS.`
    );
    app.quit();
    return;
  }

  console.log('[PHP] Binary :', phpBin);
  console.log('[PHP] DocRoot:', docRoot);
  console.log('[PHP] Port   :', port);

  const args = ['-S', `127.0.0.1:${port}`, '-t', docRoot];

  // On Windows, set SYSTEMROOT so PHP extensions can load properly
  const env = { ...process.env };
  if (process.platform === 'win32') {
    env.SYSTEMROOT = process.env.SYSTEMROOT || 'C:\\Windows';
    env.SystemRoot  = process.env.SystemRoot  || 'C:\\Windows';
  }

  phpServerProcess = spawn(phpBin, args, { cwd, env });

  phpServerProcess.stdout.on('data', d => process.stdout.write(`[PHP] ${d}`));
  phpServerProcess.stderr.on('data', d => process.stderr.write(`[PHP] ${d}`));

  phpServerProcess.on('error', err => {
    console.error('[PHP] Failed to start:', err.message);
    dialog.showErrorBox('PHP Error', `Could not start the built-in PHP server:\n${err.message}`);
  });

  phpServerProcess.on('close', code => {
    console.log(`[PHP] Server exited with code ${code}`);
  });
}

/**
 * Polls http://127.0.0.1:<port>/ until it responds, then resolves.
 * Rejects after `timeoutMs` milliseconds.
 */
function waitForServer(port, timeoutMs = 10000) {
  return new Promise((resolve, reject) => {
    const deadline = Date.now() + timeoutMs;

    const check = () => {
      if (Date.now() > deadline) {
        return reject(new Error('PHP server startup timed out'));
      }
      const req = http.request(
        { hostname: '127.0.0.1', port, method: 'GET', path: '/' },
        () => resolve()
      );
      req.on('error', () => setTimeout(check, 300));
      req.end();
    };

    check();
  });
}

// ──────────────────────────────────────────────
// Window creation
// ──────────────────────────────────────────────

/**
 * Returns a safe initial window size that fits the primary display.
 * On small Windows laptops (e.g. 1366×768) the full 1280×800 might
 * overflow – this ensures it never exceeds the available work area.
 */
function getSafeWindowSize() {
  try {
    const primaryDisplay = screen.getPrimaryDisplay();
    const { width, height } = primaryDisplay.workAreaSize;
    return {
      width:  Math.min(1280, width),
      height: Math.min(800,  height)
    };
  } catch {
    return { width: 1280, height: 800 };
  }
}

async function createWindow() {
  // Lazy-load get-port (ESM package)
  if (!getPort) {
    getPort = (await import('get-port')).default;
  }

  const port = await getPort({ port: getPort.makeRange(8000, 9000) });
  await startPhpServer(port);

  const { width, height } = getSafeWindowSize();

  mainWindow = new BrowserWindow({
    width,
    height,
    minWidth:  800,
    minHeight: 600,
    title: 'Voxxera POS',
    icon: path.join(__dirname, 'build', 'icon.png'),
    backgroundColor: '#1a1a2e',   // prevents white flash while PHP boots
    show: false,                   // reveal only after server is ready
    webPreferences: {
      nodeIntegration:  false,
      contextIsolation: true,
      // Correct font rendering on Windows – matches Chrome behaviour
      defaultFontFamily: {
        standard:  'Segoe UI',
        sansSerif: 'Segoe UI',
        monospace: 'Consolas'
      },
      defaultFontSize:       14,
      defaultMonospaceFontSize: 13
    }
  });

  // Remove default app menu (File/Edit/View/…)
  mainWindow.removeMenu();

  // Show a simple loading page while PHP boots
  mainWindow.loadURL(`data:text/html,
    <html>
    <head>
      <style>
        body { margin:0; background:#1a1a2e; display:flex; align-items:center;
               justify-content:center; height:100vh; font-family:Segoe UI,sans-serif; color:#fff; }
        .spinner { border:4px solid rgba(255,255,255,.2); border-top-color:#fff;
                   border-radius:50%; width:48px; height:48px;
                   animation:spin .8s linear infinite; margin-bottom:16px; }
        @keyframes spin { to { transform:rotate(360deg); } }
        p { margin:0; opacity:.7; }
      </style>
    </head>
    <body>
      <div style="text-align:center">
        <div class="spinner"></div>
        <p>Starting Voxxera POS…</p>
      </div>
    </body>
    </html>`);

  mainWindow.show();   // show the loading screen immediately

  // Wait for PHP to be ready (up to 12 seconds)
  try {
    await waitForServer(port, 12000);
    mainWindow.loadURL(`http://127.0.0.1:${port}`);
  } catch (err) {
    console.error('[App] PHP server not ready:', err.message);
    dialog.showErrorBox(
      'Startup Error',
      'The built-in PHP server took too long to start.\n\nPlease close any other Voxxera POS windows and try again.'
    );
    app.quit();
    return;
  }

  // Maximise automatically if the screen is large enough
  try {
    const { width: sw } = screen.getPrimaryDisplay().workAreaSize;
    if (sw >= 1400) mainWindow.maximize();
  } catch { /* ignore */ }

  mainWindow.on('closed', () => { mainWindow = null; });
}

// ──────────────────────────────────────────────
// App lifecycle
// ──────────────────────────────────────────────

app.whenReady().then(createWindow);

// macOS: re-open window when dock icon is clicked
app.on('activate', () => {
  if (mainWindow === null) createWindow();
});

// Kill PHP server when the app quits
app.on('window-all-closed', () => {
  if (phpServerProcess) {
    phpServerProcess.kill();
    phpServerProcess = null;
  }
  // On macOS, keep the process alive until Cmd+Q
  if (process.platform !== 'darwin') app.quit();
});

app.on('before-quit', () => {
  if (phpServerProcess) {
    phpServerProcess.kill();
    phpServerProcess = null;
  }
});
