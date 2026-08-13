import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  // Set VITE_TUNNEL=true when serving the dev server through an https tunnel
  // (e.g. ngrok) so the HMR websocket connects over wss:443 instead of the
  // local port. Left off, HMR behaves normally for localhost dev.
  const tunnel = env.VITE_TUNNEL === 'true'

  // Where the Vite dev-server proxy forwards /api to. Port 8001, not 8000 —
  // an unrelated Laravel app already occupies 8000 on this machine.
  const apiTarget = env.VITE_API_PROXY_TARGET || 'http://localhost:8001'

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    // Pre-bundle the Konva canvas libraries at server start. Without this Vite
    // only discovers them the first time the lazy Venue Designer chunk loads,
    // triggering a mid-session re-optimization that makes the dynamic import
    // fail ("Failed to fetch dynamically imported module") — the designer window
    // then never opens. Pre-including them fixes that.
    optimizeDeps: {
      include: ['konva', 'react-konva'],
    },
    server: {
      port: 5173,
      strictPort: true,
      // Allow tunnelling the dev server through ngrok. A leading dot whitelists
      // every subdomain, so a fresh ngrok URL each session still works.
      allowedHosts: ['.ngrok-free.dev', '.ngrok-free.app', '.ngrok.io', '.ngrok.app'],
      // Forward API calls to the local Laravel backend so the frontend can use a
      // same-origin relative base (/api/v1). This is what lets a single ngrok
      // tunnel serve both the app and its API — no second tunnel needed.
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: true,
        },
      },
      // Over an https tunnel the HMR client must reach the page host on 443/wss.
      ...(tunnel ? { hmr: { clientPort: 443 } } : {}),
    },
  }
})
