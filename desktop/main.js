import { app, BrowserWindow, shell, dialog } from 'electron';
import path from 'path';
import { spawn } from 'child_process';
import os from 'os';
import net from 'net';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

let mainWindow;
let phpServer;
let port = 8080;

// ── Helpers ────────────────────────────────────────────────────────────────

function getLocalIp() {
    const interfaces = os.networkInterfaces();
    for (const devName in interfaces) {
        const iface = interfaces[devName];
        for (let i = 0; i < iface.length; i++) {
            const alias = iface[i];
            if (alias.family === 'IPv4' && alias.address !== '127.0.0.1' && !alias.internal) {
                return alias.address;
            }
        }
    }
    return '0.0.0.0';
}

function findFreePort(startPort) {
    return new Promise((resolve) => {
        const server = net.createServer();
        server.on('error', () => {
            resolve(findFreePort(startPort + 1));
        });
        server.listen(startPort, () => {
            server.close(() => {
                resolve(startPort);
            });
        });
    });
}

/**
 * Make sure all runtime directories exist in the user's AppData folder.
 * These directories survive app installs / updates because they are
 * stored in the OS user-data location, NOT inside the installation folder.
 *
 * Windows : C:\Users\<user>\AppData\Roaming\Voxxera POS\
 * macOS   : ~/Library/Application Support/Voxxera POS/
 */
function ensureUserDataDirs(userDataPath) {
    const dirs = [
        'writable/logs',
        'writable/session',
        'writable/cache',
        'writable/uploads',
        'writable/debugbar',
        'database',         // SQLite or MySQL dump backups
    ];
    for (const dir of dirs) {
        const fullPath = path.join(userDataPath, dir);
        if (!fs.existsSync(fullPath)) {
            fs.mkdirSync(fullPath, { recursive: true });
        }
    }
}

// ── PHP server ────────────────────────────────────────────────────────────

async function startPhpServer() {
    port = await findFreePort(8080);

    // With asar:false the layout on disk is:
    //   resources/app/desktop/main.js   ← __dirname
    //   resources/app/                  ← rootPath  (app/, public/, vendor/ live here)
    //   resources/bin/php/php.exe       ← PHP binary (via extraResources)
    const rootPath   = path.join(__dirname, '..');
    const publicPath = path.join(rootPath, 'public');

    // ── Resolve PHP binary ──────────────────────────────────────────────────
    // CRITICAL FIX: extraResources are copied OUTSIDE the app directory to
    // <resources>/bin/…  Use process.resourcesPath, NOT __dirname.
    let phpBinary = 'php'; // fallback to system PHP on PATH
    if (process.platform === 'win32') {
        const bundledPhp = path.join(process.resourcesPath, 'bin', 'php', 'php.exe');
        if (fs.existsSync(bundledPhp)) {
            phpBinary = bundledPhp;
        }
    } else {
        const bundledPhp = path.join(process.resourcesPath, 'bin', 'php', 'php');
        if (fs.existsSync(bundledPhp)) {
            phpBinary = bundledPhp;
        }
    }

    // Bail out with a friendly dialog if PHP cannot be found at all
    if (phpBinary === 'php') {
        try {
            // Quick sync check: will 'php -v' work?
            const { execSync } = await import('child_process');
            execSync('php -v', { stdio: 'ignore' });
        } catch {
            dialog.showErrorBox(
                'PHP not found',
                'Voxxera POS could not find PHP.\n\n' +
                'Please re-install the application so that the bundled PHP is restored.'
            );
            app.quit();
            return;
        }
    }

    // ── Persistent data directory ───────────────────────────────────────────
    // Store logs, sessions, cache and uploads in the OS user-data folder so
    // that data is never lost when the app is updated or re-installed.
    const userDataPath = app.getPath('userData');
    ensureUserDataDirs(userDataPath);

    // Point CodeIgniter 4's WRITEPATH to the persistent directory.
    // CI4 respects the WRITEPATH environment variable if set.
    const writablePath = path.join(userDataPath, 'writable') + path.sep;

    const rewriteScript = path.join(
        rootPath,
        'vendor', 'codeigniter4', 'framework', 'system', 'rewrite.php'
    );

    console.log(`[Voxxera] PHP binary : ${phpBinary}`);
    console.log(`[Voxxera] Root path  : ${rootPath}`);
    console.log(`[Voxxera] Public path: ${publicPath}`);
    console.log(`[Voxxera] Writable   : ${writablePath}`);
    console.log(`[Voxxera] Port       : ${port}`);

    phpServer = spawn(phpBinary, [
        '-S', `0.0.0.0:${port}`,
        '-t', publicPath,
        rewriteScript,
    ], {
        cwd: rootPath,
        env: {
            ...process.env,
            WRITEPATH: writablePath,
            CI_ENVIRONMENT: 'production',
        },
    });

    phpServer.stdout.on('data', (data) => console.log(`[PHP] ${data}`));
    phpServer.stderr.on('data', (data) => process.stderr.write(`[PHP] ${data}`));

    phpServer.on('error', (err) => {
        dialog.showErrorBox(
            'PHP server failed to start',
            `Could not start the built-in PHP server:\n\n${err.message}\n\nPath tried: ${phpBinary}`
        );
    });

    // Give the PHP server a moment to bind the port
    await new Promise((r) => setTimeout(r, 800));

    return port;
}

// ── Electron window ────────────────────────────────────────────────────────

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        title: 'Voxxera POS',
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.cjs'),
        },
    });

    mainWindow.loadURL(`http://localhost:${port}`);

    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // ── Network access banner ─────────────────────────────────────────────
    mainWindow.webContents.on('did-finish-load', () => {
        const localIp = getLocalIp();
        const bannerScript = `
            (function () {
                if (document.getElementById('voxxera-net-banner')) return;
                const banner = document.createElement('div');
                banner.id = 'voxxera-net-banner';
                banner.style.cssText = [
                    'background:#2c3e50',
                    'color:white',
                    'padding:4px 20px',
                    'text-align:center',
                    'font-size:12px',
                    'position:fixed',
                    'bottom:0',
                    'left:0',
                    'width:100%',
                    'z-index:9999',
                    'box-sizing:border-box',
                ].join(';');
                banner.innerHTML = '🌐 Network Access: <strong>http://${localIp}:${port}</strong> &nbsp;|&nbsp; Local Server Running';
                document.body.appendChild(banner);
            })();
        `;
        mainWindow.webContents.executeJavaScript(bannerScript);
    });

    // Open external links in the system browser
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });
}

// ── App lifecycle ──────────────────────────────────────────────────────────

app.on('ready', async () => {
    await startPhpServer();
    createWindow();
});

app.on('window-all-closed', () => {
    if (phpServer) phpServer.kill();
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (mainWindow === null) {
        createWindow();
    }
});

app.on('will-quit', () => {
    if (phpServer) phpServer.kill();
});
