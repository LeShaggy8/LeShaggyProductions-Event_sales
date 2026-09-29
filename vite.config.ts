import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// base '/' porque el sitio se sirve desde la raíz del dominio (public_html)
export default defineConfig({ base: '/', plugins: [react(), tailwindcss()] })
