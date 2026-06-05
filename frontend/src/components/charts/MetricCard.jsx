import React from 'react'
import { motion } from 'framer-motion'
import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '../../lib/cn'
import { Skeleton } from '../ui/Skeleton'

export function MetricCard({ label, value, subValue, icon: Icon, iconColor, trend, loading, className }) {
  if (loading) {
    return (
      <div className={cn('rounded-xl bg-[var(--surface)] border border-[var(--border)] p-5 shadow-[var(--shadow-sm)]', className)}>
        <Skeleton className="h-3 w-24 mb-3" />
        <Skeleton className="h-9 w-32 mb-2" />
        <Skeleton className="h-3 w-16" />
      </div>
    )
  }

  const trendPositive = trend > 0
  const trendNeutral  = trend === 0 || trend === undefined || trend === null
  const TrendIcon     = trendNeutral ? Minus : trendPositive ? TrendingUp : TrendingDown

  return (
    <motion.div
      className={cn(
        'rounded-xl bg-[var(--surface)] border border-[var(--border)]',
        'p-5 shadow-[var(--shadow-sm)]',
        'hover:shadow-[var(--shadow-md)] transition-shadow duration-200',
        className
      )}
      whileHover={{ y: -1 }}
      transition={{ duration: 0.15 }}
    >
      <div className="flex items-start justify-between mb-3">
        <p className="text-xs font-medium text-[var(--text-tertiary)] uppercase tracking-wide">{label}</p>
        {Icon && (
          <div className={cn(
            'flex h-8 w-8 items-center justify-center rounded-lg',
            iconColor ?? 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400'
          )}>
            <Icon size={16} />
          </div>
        )}
      </div>
      <p className="text-3xl font-bold text-[var(--text-primary)] tabular leading-none mb-1.5">
        {value}
      </p>
      <div className="flex items-center gap-2">
        {!trendNeutral && (
          <div className={cn(
            'flex items-center gap-1 text-xs font-medium',
            trendPositive ? 'text-emerald-600' : 'text-red-500'
          )}>
            <TrendIcon size={12} />
            <span>{Math.abs(trend)}%</span>
          </div>
        )}
        {subValue && (
          <p className="text-xs text-[var(--text-tertiary)]">{subValue}</p>
        )}
      </div>
    </motion.div>
  )
}
