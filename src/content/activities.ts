export type Activity = { id: string; title: string; text?: string; confirmed: boolean }

// Para mostrar una actividad en el sitio: confirmed: true (y opcionalmente `text`).
// Para quitarla: confirmed: false o borrar la línea. No hay que tocar componentes.
export const activities: Activity[] = [
  { id: 'arcade', title: 'Arcade', confirmed: false },
  { id: 'tatuajes', title: 'Tatuajes', confirmed: false },
  { id: 'vendedores', title: 'Vendedores locales', confirmed: false },
  { id: 'patrocinadores', title: 'Patrocinadores', confirmed: false },
]
