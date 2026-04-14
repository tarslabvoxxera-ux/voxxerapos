// preload.cjs — Must be CommonJS because Electron sandboxed preload
// cannot use ES module syntax regardless of package.json "type"
const { contextBridge } = require('electron');
const os = require('os');

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
    return '127.0.0.1';
}

contextBridge.exposeInMainWorld('voxxera', {
    localIp: getLocalIp(),
    platform: process.platform,
});
