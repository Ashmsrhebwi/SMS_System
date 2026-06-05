import React from 'react'
import { cn } from '../../lib/cn'

export function Card({ children, className, padding = true, hover = false, ...props }) {
  return (
    <div
      className={cn(
        'rounded-xl bg-[var(--surface)] border border-[var(--border)]',
        'shadow-[var(--shadow-sm)]',
        padding && 'p-5',
        hover && 'transition-shadow duration-200 hover:shadow-[var(--shadow-md)] cursor-pointer',
        className
      )}
      {...props}
    >
      {children}
    </div>
  )
}

export function CardHeader({ title, subtitle, action, className }) {
  return (
    <div className={cn('flex items-start justify-between mb-4', className)}>
      <div>
        <h3 className="text-sm font-semibold text-[var(--text-primary)]">{title}</h3>
        {subtitle && <p className="text-xs text-[var(--text-tertiary)] mt-0.5">{subtitle}</p>}
      </div>
      {action && <div className="shrink-0 ml-4">{action}</div>}
    </div>
  )
}
