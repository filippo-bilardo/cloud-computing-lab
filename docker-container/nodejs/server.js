'use strict';

const express = require('express');
const si = require('systeminformation');
const path = require('path');
const os = require('os');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.static(path.join(__dirname, 'public')));

// --- API: static container/system info ---
app.get('/api/info', async (req, res) => {
  try {
    const [cpu, mem, osInfo, dockerInfo] = await Promise.all([
      si.cpu(),
      si.mem(),
      si.osInfo(),
      si.dockerInfo().catch(() => null),
    ]);

    res.json({
      container: {
        hostname: os.hostname(),
        platform: process.platform,
        arch: process.arch,
        nodeVersion: process.version,
        pid: process.pid,
        startTime: new Date(Date.now() - process.uptime() * 1000).toISOString(),
        env: {
          NODE_ENV: process.env.NODE_ENV || 'development',
          PORT: process.env.PORT || '3000',
        },
      },
      os: {
        distro: osInfo.distro,
        release: osInfo.release,
        kernel: osInfo.kernel,
        arch: osInfo.arch,
      },
      cpu: {
        manufacturer: cpu.manufacturer,
        brand: cpu.brand,
        cores: cpu.cores,
        physicalCores: cpu.physicalCores,
        speed: cpu.speed,
      },
      memory: {
        total: mem.total,
        free: mem.free,
        used: mem.used,
        active: mem.active,
      },
      docker: dockerInfo
        ? {
            containers: dockerInfo.containers,
            containersRunning: dockerInfo.containersRunning,
            images: dockerInfo.images,
            serverVersion: dockerInfo.serverVersion,
          }
        : null,
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// --- API: real-time metrics (polled every 2s by the frontend) ---
app.get('/api/metrics', async (req, res) => {
  try {
    const [cpuLoad, mem, netStats, diskLoad, processes] = await Promise.all([
      si.currentLoad(),
      si.mem(),
      si.networkStats().catch(() => []),
      si.disksIO().catch(() => null),
      si.processes(),
    ]);

    const net = netStats[0] || {};

    res.json({
      timestamp: Date.now(),
      uptime: process.uptime(),
      cpu: {
        load: parseFloat(cpuLoad.currentLoad.toFixed(2)),
        user: parseFloat(cpuLoad.currentLoadUser.toFixed(2)),
        system: parseFloat(cpuLoad.currentLoadSystem.toFixed(2)),
        idle: parseFloat((100 - cpuLoad.currentLoad).toFixed(2)),
      },
      memory: {
        total: mem.total,
        used: mem.used,
        free: mem.free,
        active: mem.active,
        usedPercent: parseFloat(((mem.used / mem.total) * 100).toFixed(2)),
      },
      network: {
        iface: net.iface || 'n/a',
        rxSec: net.rx_sec != null ? Math.round(net.rx_sec) : 0,
        txSec: net.tx_sec != null ? Math.round(net.tx_sec) : 0,
      },
      disk: diskLoad
        ? {
            readSec: Math.round(diskLoad.rIO_sec || 0),
            writeSec: Math.round(diskLoad.wIO_sec || 0),
          }
        : null,
      processes: {
        all: processes.all,
        running: processes.running,
        sleeping: processes.sleeping,
      },
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.listen(PORT, () => {
  console.log(`Container Dashboard running on http://0.0.0.0:${PORT}`);
});
