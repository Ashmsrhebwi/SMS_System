import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Users, Send, CheckCircle2, MousePointerClick,
  DollarSign, UserX, ArrowRight, Clock
} from 'lucide-react'
import api from '../services/api'
import { PageHeader } from '../components/layout/PageHeader'
import { MetricCard } from '../components/charts/MetricCard'
import { DeliveryChart } from '../components/charts/DeliveryChart'
import { Card, CardHeader } from '../components/ui/Card'
import { StatusBadge } from '../components/ui/Badge'
import { Skeleton } from '../components/ui/Skeleton'
import { staggerContainer, staggerItem } from '../lib/animations'

export default function DashboardPage() {
  const [data, setData]         = useState(null)
  const [delivery, setDelivery] = useState([])
  const [loading, setLoading]   = useState(true)

  useEffect(() => {
    Promise.all([
      api.get('/dashboard'),
      api.get('/reports/delivery', { params: { days: 30 } }),
    ]).then(([d, dl]) => {
      setData(d.data)
      setDelivery(dl.data.data ?? [])
    }).finally(() => setLoading(false))
  }, [])

  const stats = data?.stats ?? {}
  const sym   = stats.currency_symbol ?? '$'

  const metrics = [
    { label: 'Total Contacts',   value: stats.total_contacts?.toLocaleString()      ?? '—', icon: Users,              iconColor: 'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400' },
    { label: 'Total Campaigns',  value: stats.total_campaigns?.toLocaleString()     ?? '—', icon: Send,               iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
    { label: 'Delivered',        value: stats.total_delivered?.toLocaleString()     ?? '—', icon: CheckCircle2,       iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
    { label: 'Delivery Rate',    value: `${stats.delivery_rate ?? 0}%`,                      icon: MousePointerClick,  iconColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400' },
    { label: 'Monthly Cost',     value: `${sym}${(stats.cost_this_month ?? 0).toFixed(2)}`,  icon: DollarSign,         iconColor: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400' },
    { label: 'Opted Out',        value: ((stats.total_contacts ?? 0) - (stats.opted_in ?? 0)).toLocaleString(), icon: UserX, iconColor: 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' },
  ]

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        subtitle="Platform overview and recent activity"
        action={
          <Link to="/campaigns/new">
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition-colors"
            >
              <Send size={14} />
              New Campaign
            </motion.button>
          </Link>
        }
      />

      {/* Metrics grid */}
      <motion.div
        className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6"
        variants={staggerContainer}
        initial="initial"
        animate="animate"
      >
        {metrics.map((m) => (
          <motion.div key={m.label} variants={staggerItem}>
            <MetricCard {...m} loading={loading} />
          </motion.div>
        ))}
      </motion.div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* Delivery chart */}
        <Card className="lg:col-span-3">
          <CardHeader
            title="Delivery Trend"
            subtitle="Last 30 days"
            action={
              <Link to="/reports" className="text-xs text-brand-600 hover:text-brand-700 flex items-center gap-1">
                Full report <ArrowRight size={12} />
              </Link>
            }
          />
          <DeliveryChart data={delivery} loading={loading} />
        </Card>

        {/* Top countries */}
        <Card className="lg:col-span-2">
          <CardHeader title="Top Countries" subtitle="By contact volume" />
          {loading ? (
            <div className="space-y-3">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="flex items-center gap-3">
                  <Skeleton className="h-3 w-20" />
                  <Skeleton className="flex-1 h-2 rounded-full" />
                  <Skeleton className="h-3 w-8" />
                </div>
              ))}
            </div>
          ) : (
            <div className="space-y-3">
              {(data?.top_countries ?? []).map(({ country, count }, i) => {
                const max = data?.top_countries?.[0]?.count ?? 1
                return (
                  <div key={country} className="flex items-center gap-3">
                    <span className="text-xs text-[var(--text-secondary)] w-20 truncate">{country}</span>
                    <div className="flex-1 h-1.5 bg-[var(--surface-2)] rounded-full overflow-hidden">
                      <motion.div
                        className="h-full bg-brand-500 rounded-full"
                        initial={{ width: 0 }}
                        animate={{ width: `${(count / max) * 100}%` }}
                        transition={{ duration: 0.6, delay: i * 0.05 }}
                      />
                    </div>
                    <span className="text-xs tabular text-[var(--text-tertiary)] w-8 text-right">{count}</span>
                  </div>
                )
              })}
            </div>
          )}
        </Card>
      </div>

      {/* Recent campaigns */}
      <Card padding={false}>
        <div className="px-5 py-4 border-b border-[var(--border)] flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-[var(--text-primary)]">Recent Campaigns</h3>
            <p className="text-xs text-[var(--text-tertiary)] mt-0.5">Latest campaign activity</p>
          </div>
          <Link to="/campaigns" className="text-xs text-brand-600 hover:text-brand-700 flex items-center gap-1 font-medium">
            View all <ArrowRight size={12} />
          </Link>
        </div>
        {loading ? (
          <div className="divide-y divide-[var(--border)]">
            {[...Array(5)].map((_, i) => (
              <div key={i} className="flex items-center gap-4 px-5 py-3.5">
                <Skeleton className="h-4 flex-1" />
                <Skeleton className="h-5 w-20 rounded-full" />
                <Skeleton className="h-4 w-16" />
                <Skeleton className="h-4 w-24" />
              </div>
            ))}
          </div>
        ) : (
          <div className="divide-y divide-[var(--border)]">
            {(data?.recent_campaigns ?? []).map(c => (
              <Link
                key={c.id}
                to={`/campaigns/${c.id}`}
                className="flex items-center gap-4 px-5 py-3.5 hover:bg-[var(--surface-2)] transition-colors group"
              >
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[var(--text-primary)] group-hover:text-brand-600 transition-colors truncate">
                    {c.name}
                  </p>
                </div>
                <StatusBadge status={c.status} />
                <span className="text-xs tabular text-[var(--text-tertiary)] shrink-0">
                  {c.total_recipients?.toLocaleString() ?? 0} recipients
                </span>
                <span className="text-xs text-[var(--text-tertiary)] shrink-0 flex items-center gap-1">
                  <Clock size={11} />
                  {new Date(c.created_at).toLocaleDateString()}
                </span>
              </Link>
            ))}
            {!data?.recent_campaigns?.length && (
              <p className="py-10 text-center text-sm text-[var(--text-tertiary)]">No campaigns yet</p>
            )}
          </div>
        )}
      </Card>
    </div>
  )
}
