# SpookyPeda Vol. V — landing

Vite + React + TypeScript + Tailwind CSS. Sitio estático, sin Node en producción.

## Desarrollo
```
npm install
npm run dev
```

## Despliegue (HostGator)
```
npm run build
```
Subir el **contenido** de `dist/` al directorio público del dominio (`public_html`).

## Dónde cambiar cosas
- Destino de compra: `src/config/site.ts` (o `VITE_PURCHASE_URL` al compilar).
- Datos del evento: `src/content/event.ts`. Precios y fases: `src/content/pricing.ts` (mantener en sincronía con WooCommerce).
- Colores y glow: `src/styles/index.css`.
- Alien real: reemplazar el placeholder en `src/components/features/HeroVisual.tsx`.
