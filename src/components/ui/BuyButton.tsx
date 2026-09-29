import type { ReactNode } from 'react'
import { PURCHASE_URL } from '../../config/site'

type Props = { children?: ReactNode; small?: boolean }

export default function BuyButton({ children = 'Comprar boleto', small = false }: Props) {
  const external = PURCHASE_URL.startsWith('http')
  return (
    <a
      href={PURCHASE_URL}
      {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
      className={`glow-neon inline-flex items-center justify-center rounded-full bg-neon font-display text-void transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan ${
        small ? 'px-4 py-2 text-sm' : 'px-7 py-3.5 text-base'
      }`}
    >
      {children}
    </a>
  )
}
