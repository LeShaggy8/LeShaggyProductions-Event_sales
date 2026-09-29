// PLACEHOLDER del alienígena. Para usar el asset real del diseñador, reemplazar
// el <svg> por <img src={alien} alt="" className="..."/> conservando el contenedor.
export default function HeroVisual({ className = '' }: { className?: string }) {
  return (
    <div className={`relative mx-auto aspect-square w-full max-w-[22rem] md:max-w-[30rem] ${className}`} aria-hidden="true">
      <div className="absolute inset-0 rounded-full bg-violet/30 blur-3xl" />
      <svg viewBox="0 0 400 400" className="animate-float relative h-full w-full text-neon">
        <circle cx="200" cy="200" r="190" fill="none" stroke="var(--color-cyan)" strokeOpacity=".35" />
        <circle cx="200" cy="200" r="150" fill="none" stroke="var(--color-blue)" strokeOpacity=".45" strokeDasharray="4 10" />
        <path
          d="M200 100c66 0 104 50 98 112-4 42-42 88-98 104-56-16-94-62-98-104-6-62 32-112 98-112z"
          fill="#07130d"
          stroke="currentColor"
          strokeWidth="3"
          style={{ filter: 'drop-shadow(0 0 14px rgb(57 255 136 / .6))' }}
        />
        <ellipse cx="160" cy="215" rx="26" ry="14" transform="rotate(20 160 215)" fill="currentColor" />
        <ellipse cx="240" cy="215" rx="26" ry="14" transform="rotate(-20 240 215)" fill="currentColor" />
      </svg>
    </div>
  )
}
