import { app, BrowserWindow, shell, dialog, ipcMain } from 'electron';
import path from 'path';
import { spawn } from 'child_process';
import os from 'os';
import net from 'net';
import { fileURLToPath } from 'url';
import fs from 'fs';
import { autoUpdater } from 'electron-updater';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

let mainWindow;
let phpServer;
let mariadbServer;
let port = 8080;

// ── Auto-Updater Setup ──────────────────────────────────────────────────────
autoUpdater.autoDownload = false;

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
    const userDataPath = app.getPath('userData');
    ensureUserDataDirs(userDataPath);
    const writablePath = path.join(userDataPath, 'writable') + path.sep;
    const dbPath = path.join(userDataPath, 'database');

    // ── Start Portable MariaDB ──────────────────────────────────────────────
    let dbPort = 3306; // Fallback to external
    const bundledMaria = path.join(process.resourcesPath, 'bin', 'mariadb');
    if (fs.existsSync(bundledMaria)) {
        dbPort = 33089; // Use custom port to avoid conflicts
        const mariadbDataPath = path.join(dbPath, 'data');
        const socketPath = path.join(dbPath, 'mysql.sock');

        if (!fs.existsSync(mariadbDataPath)) {
            // First run: Initialize database
            const installBin = process.platform === 'win32' 
                ? path.join(bundledMaria, 'bin', 'mysql_install_db.exe')
                : path.join(bundledMaria, 'scripts', 'mysql_install_db');
                
            try {
                const { execSync } = await import('child_process');
                execSync(`"${installBin}" --basedir="${bundledMaria}" --datadir="${mariadbDataPath}"`, { stdio: 'ignore' });
                
                // Initialize default database dump if exists
                const initSql = path.join(rootPath, 'app', 'Database', 'database.sql');
                if (fs.existsSync(initSql)) {
                    // Start temporarily to import, but this may be tricky in Electron shell, 
                    // CI4 migrations ideally handle seeds, or the user simply restores a backup.
                }
            } catch (e) {
                console.error("MariaDB init failed: ", e);
            }
        }

        const daemonBin = process.platform === 'win32'
            ? path.join(bundledMaria, 'bin', 'mysqld.exe')
            : path.join(bundledMaria, 'bin', 'mariadbd');

        mariadbServer = spawn(daemonBin, [
            `--basedir=${bundledMaria}`,
            `--datadir=${mariadbDataPath}`,
            `--port=${dbPort}`,
            `--socket=${socketPath}`
        ]);
        
        mariadbServer.stdout.on('data', d => console.log(`[DB] ${d}`));
        mariadbServer.stderr.on('data', d => console.error(`[DB] ${d}`));
    }

    const rewriteScript = path.join(
        rootPath,
        'vendor', 'codeigniter4', 'framework', 'system', 'rewrite.php'
    );

    phpServer = spawn(phpBinary, [
        '-S', `0.0.0.0:${port}`,
        '-t', publicPath,
        rewriteScript,
    ], {
        cwd: rootPath,
        env: {
            ...process.env,
            'WRITEPATH': writablePath,
            'CI_ENVIRONMENT': 'production',
            'database.default.hostname': '127.0.0.1',
            'database.default.port': dbPort.toString()
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

    // ── Auto-Updater ────────────────────────────────────────────────────────
    autoUpdater.checkForUpdatesAndNotify();

    autoUpdater.on('update-available', () => {
        dialog.showMessageBox(mainWindow, {
            type: 'info',
            title: 'Update Available',
            message: 'A new version of Voxxera POS is available. Do you want to download it now?',
            buttons: ['Yes', 'No']
        }).then((result) => {
            if (result.response === 0) autoUpdater.downloadUpdate();
        });
    });

    autoUpdater.on('update-downloaded', () => {
        dialog.showMessageBox(mainWindow, {
            type: 'question',
            title: 'Install Update',
            message: 'Update downloaded. The application will restart to install it.',
            buttons: ['Restart Now', 'Later']
        }).then((result) => {
            if (result.response === 0) autoUpdater.quitAndInstall();
        });
    });
}

// ── Database Backup & Restore ──────────────────────────────────────────────

ipcMain.handle('backup-db', async () => {
    const userDataPath = app.getPath('userData');
    // For SQLite, this points to database directory or voxxera.sqlite
    const dbPath = path.join(userDataPath, 'database'); 

    if (!fs.existsSync(dbPath)) return { success: false, msg: 'No database found to backup.' };
    
    // Pick save location via standard Electron dialog window
    const { canceled, filePath } = await dialog.showSaveDialog(mainWindow, {
        title: 'Export Database Backup',
        defaultPath: 'voxxera_backup',
    });

    if (canceled || !filePath) return { success: false, msg: 'Canceled by user.' };

    try {
        fs.cpSync(dbPath, filePath, { recursive: true });
        return { success: true, msg: 'Backup exported successfully!' };
    } catch (e) {
        return { success: false, msg: e.message };
    }
});

ipcMain.handle('restore-db', async () => {
    const { canceled, filePaths } = await dialog.showOpenDialog(mainWindow, {
        title: 'Restore Database',
        properties: ['openDirectory', 'openFile']
    });

    if (canceled || filePaths.length === 0) return { success: false, msg: 'Canceled by user.' };

    try {
        const userDataPath = app.getPath('userData');
        const dbPath = path.join(userDataPath, 'database');
        
        fs.cpSync(filePaths[0], dbPath, { recursive: true });
        return { success: true, msg: 'Database restored! Please restart the app.' };
    } catch (e) {
        return { success: false, msg: e.message };
    }
});

// ── App lifecycle ──────────────────────────────────────────────────────────

app.on('ready', async () => {
    await startPhpServer();
    createWindow();
});

app.on('window-all-closed', () => {
    if (phpServer) phpServer.kill();
    if (mariadbServer) mariadbServer.kill();
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
    if (mariadbServer) mariadbServer.kill();
});
