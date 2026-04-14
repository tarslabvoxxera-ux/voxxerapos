import { app, BrowserWindow, shell } from 'electron';
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

async function startPhpServer() {
    port = await findFreePort(8080);
    const rootPath = path.join(__dirname, '..');
    const publicPath = path.join(rootPath, 'public');
    
    // Determine PHP binary path
    let phpBinary = 'php'; // Default to system PHP
    if (process.platform === 'win32') {
        const bundledPhp = path.join(__dirname, 'bin', 'php', 'php.exe');
        if (fs.existsSync(bundledPhp)) phpBinary = bundledPhp;
    } else {
        const bundledPhp = path.join(__dirname, 'bin', 'php', 'php');
        if (fs.existsSync(bundledPhp)) phpBinary = bundledPhp;
    }

    const rewriteScript = path.join(rootPath, 'vendor', 'codeigniter4', 'framework', 'system', 'rewrite.php');

    console.log(`Starting PHP server on port ${port} using ${phpBinary}...`);
    
    phpServer = spawn(phpBinary, [
        '-S', `0.0.0.0:${port}`,
        '-t', publicPath,
        rewriteScript
    ], {
        cwd: rootPath,
    });

    phpServer.stdout.on('data', (data) => console.log(`PHP: ${data}`));
    phpServer.stderr.on('data', (data) => console.error(`PHP Error: ${data}`));

    return port;
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        title: 'Voxxera POS',
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.cjs')
        }
    });

    const localIp = getLocalIp();
    mainWindow.loadURL(`http://localhost:${port}`);

    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // Inject Connectivity Banner
    mainWindow.webContents.on('did-finish-load', () => {
        const localIp = getLocalIp();
        const bannerScript = `
            const banner = document.createElement('div');
            banner.style.background = '#2c3e50';
            banner.style.color = 'white';
            banner.style.padding = '5px 20px';
            banner.style.textAlign = 'center';
            banner.style.fontSize = '12px';
            banner.style.position = 'fixed';
            banner.style.bottom = '0';
            banner.style.width = '100%';
            banner.style.zIndex = '9999';
            banner.innerHTML = '🌐 Network Access: <strong>http://${localIp}:${port}</strong> | Status: Local Server Running';
            document.body.appendChild(banner);
        `;
        mainWindow.webContents.executeJavaScript(bannerScript);
    });

    // Open links in external browser
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });
}

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
