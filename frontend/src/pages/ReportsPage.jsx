import React, { useEffect, useState } from 'react'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, LineChart, Line } from 'recharts'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'

export default function ReportsPage() {
  const { user }                  = useAuth()
  const [costs, setCosts]         = useState(null)
  const [delivery, setDelivery]   = useState([])
  const [countries, setCountries] = useState([])
  const [loading, setLoading]     = useState(true)

  useEffect(() => {
    if (user?.role !== 'admin') return
    Promise.all([
      api.get('/reports/costs'),
      api.get('/reports/delivery', { params: { days: 30 } }),
      api.get('/reports/countries'),
    ]).then(([c, d, k]) => {
      setCosts(c.data)
      setDelivery(d.data.data ?? [])
      setCountries(k.data.data?.slice(0, 10) ?? [])
    }).finally(() => setLoading(false))
  }, [])

  if (user?.role !== 'admin') {
    return <div className="card text-center py-12 text-gray-500">Reports are available to admins only.</div>
  }

  if (loading) return <div className="flex items-center justify-center h-64 text-gray-400">Loading…</div>

  const sym = costs?.summary?.currency_symbol ?? '$'

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Reports</h1>

      {/* Cost summary */}
      {costs?.summary && (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {[
            { label: 'Total Messages', value: costs.summary.total_messages.toLocaleString() },
            { label: 'Total Segments', value: costs.summary.total_segments.toLocaleString() },
            { label: 'Cost This Month', value: `${sym}${costs.summary.cost_this_month.toFixed(2)}` },
            { label: 'Total Cost', value: `${sym}${costs.summary.total_cost.toFixed(2)}` },
          ].map(({ label, value }) => (
            <div key={label} className="card">
              <p className="text-sm text-gray-500">{label}</p>
              <p className="text-2xl font-bold text-gray-900">{value}</p>
            </div>
          ))}
        </div>
      )}

      {/* Delivery trend */}
      {delivery.length > 0 && (
        <div className="card">
          <h2 className="font-semibold text-gray-900 mb-4">Delivery Trend (Last 30 Days)</h2>
          <ResponsiveContainer width="100%" height={240}>
            <LineChart data={delivery}>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
              <XAxis dataKey="date" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip />
              <Line type="monotone" dataKey="delivered" stroke="#22c55e" strokeWidth={2} dot={false} name="Delivered" />
              <Line type="monotone" dataKey="failed" stroke="#ef4444" strokeWidth={2} dot={false} name="Failed" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      )}

      {/* Top countries */}
      {countries.length > 0 && (
        <div className="card">
          <h2 className="font-semibold text-gray-900 mb-4">Top Countries</h2>
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={countries} layout="vertical">
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
              <XAxis type="number" tick={{ fontSize: 11 }} />
              <YAxis dataKey="country" type="category" width={100} tick={{ fontSize: 11 }} />
              <Tooltip />
              <Bar dataKey="contacts" fill="#6366f1" name="Contacts" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      )}

      {/* Campaign cost table */}
      {costs?.data?.data?.length > 0 && (
        <div className="card p-0 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-200 font-medium text-sm text-gray-700">Campaign Costs</div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-2 text-left font-medium text-gray-600">Campaign</th>
                <th className="px-4 py-2 text-left font-medium text-gray-600">Status</th>
                <th className="px-4 py-2 text-right font-medium text-gray-600">Messages</th>
                <th className="px-4 py-2 text-right font-medium text-gray-600">Segments</th>
                <th className="px-4 py-2 text-right font-medium text-gray-600">Cost</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {costs.data.data.map(c => (
                <tr key={c.id} className="hover:bg-gray-50">
                  <td className="px-4 py-2 font-medium text-gray-900">{c.name}</td>
                  <td className="px-4 py-2 text-gray-500 capitalize">{c.status}</td>
                  <td className="px-4 py-2 text-right text-gray-600">{c.message_count}</td>
                  <td className="px-4 py-2 text-right text-gray-600">{c.total_segments ?? 0}</td>
                  <td className="px-4 py-2 text-right text-gray-600">{sym}{parseFloat(c.total_cost ?? 0).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
