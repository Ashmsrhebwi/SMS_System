import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import StatusBadge from '../components/StatusBadge'

function StatCard({ label, value, sub }) {
  return (
    <div className="card">
      <p className="text-sm font-medium text-gray-500">{label}</p>
      <p className="mt-1 text-3xl font-bold text-gray-900">{value}</p>
      {sub && <p className="mt-1 text-xs text-gray-500">{sub}</p>}
    </div>
  )
}

export default function DashboardPage() {
  const [data, setData]       = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/dashboard').then(r => setData(r.data)).finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="flex items-center justify-center h-64 text-gray-400">Loading…</div>
  if (!data) return null

  const { stats, recent_campaigns, recent_activity, top_countries } = data
  const sym = stats.currency_symbol

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

      {/* Stats grid */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <StatCard label="Total Contacts"   value={stats.total_contacts.toLocaleString()} />
        <StatCard label="Opted In"         value={stats.opted_in.toLocaleString()} />
        <StatCard label="Total Campaigns"  value={stats.total_campaigns.toLocaleString()} />
        <StatCard label="Messages Sent"    value={stats.total_messages_sent.toLocaleString()} />
        <StatCard label="Delivery Rate"    value={`${stats.delivery_rate}%`} />
        <StatCard label="Click Rate"       value={`${stats.click_rate}%`} />
        <StatCard label="Cost This Month"  value={`${sym}${stats.cost_this_month.toFixed(2)}`} />
        <StatCard label="Total Cost"       value={`${sym}${stats.cost_total.toFixed(2)}`} />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Recent campaigns */}
        <div className="card">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-semibold text-gray-900">Recent Campaigns</h2>
            <Link to="/campaigns" className="text-sm text-brand-600 hover:text-brand-700">View all →</Link>
          </div>
          <div className="divide-y divide-gray-100">
            {recent_campaigns.map(c => (
              <div key={c.id} className="flex items-center justify-between py-3">
                <div>
                  <Link to={`/campaigns/${c.id}`} className="text-sm font-medium text-gray-900 hover:text-brand-600">{c.name}</Link>
                  <p className="text-xs text-gray-500">{c.total_recipients} recipients</p>
                </div>
                <StatusBadge status={c.status} />
              </div>
            ))}
            {recent_campaigns.length === 0 && <p className="py-6 text-center text-sm text-gray-400">No campaigns yet</p>}
          </div>
        </div>

        {/* Top countries */}
        <div className="card">
          <h2 className="font-semibold text-gray-900 mb-4">Top Countries</h2>
          <div className="space-y-3">
            {top_countries.map(({ country, count }) => (
              <div key={country} className="flex items-center gap-3">
                <span className="text-sm font-medium text-gray-700 w-24 truncate">{country}</span>
                <div className="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-brand-500 rounded-full"
                    style={{ width: `${(count / (top_countries[0]?.count || 1)) * 100}%` }}
                  />
                </div>
                <span className="text-xs text-gray-500 w-8 text-right">{count}</span>
              </div>
            ))}
            {top_countries.length === 0 && <p className="text-sm text-gray-400">No data yet</p>}
          </div>
        </div>
      </div>
    </div>
  )
}
