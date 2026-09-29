export type Media = { id: string; kind: 'photo' | 'video'; src?: string; alt: string }

// Sin `src` se muestra un placeholder. Poner la ruta (ej. '/images/vol-4-01.webp') para activar.
export const media: Media[] = [
  { id: 'm1', kind: 'video', alt: 'Video de una edición anterior' },
  { id: 'm2', kind: 'photo', alt: 'Foto de una edición anterior' },
  { id: 'm3', kind: 'photo', alt: 'Foto de una edición anterior' },
  { id: 'm4', kind: 'photo', alt: 'Foto de una edición anterior' },
  { id: 'm5', kind: 'photo', alt: 'Foto de una edición anterior' },
  { id: 'm6', kind: 'photo', alt: 'Foto de una edición anterior' },
]
