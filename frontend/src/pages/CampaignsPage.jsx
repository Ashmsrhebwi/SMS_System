import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import StatusBadge from '../components/StatusBadge'
import Pagination from '../components/Pagination'

export default function CampaignsPage() {
  const [data, setData]       = useState(null)
  const [page, setPage]       = useState(1)
  const [search, setSearch]   = useState('')
  const [status, setStatus]   = useState('')
  const [loading, setLoading] = useState(true)

  const load = (p = page) => {
    setLoading(true)
    api.get('/campaigns', { params: { page: p, search, status } })
      .then(r => setData(r.data))
      .finally(() => setLoading(false))
  }

  useEffect(() => { load(1); setPage(1) }, [search, status])
  useEffect(() => { load(page) }, [page])

  const handleSearch = (e) => {
    e.preventDefault()
    load(1)
    setPage(1)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Campaigns</h1>
        <Link to="/campaigns/new" className="btn-primary">+ New Campaign</Link>
      </div>

      {/* Filters */}
      <form onSubmit={handleSearch} className="flex gap-2">
        <input
          className="input w-64"
          placeholder="Search campaigns…"
          value={search}
          onChange={e => setSearch(e.target.value)}
        />
        <select className="input w-40" value={status} onChange={e => setStatus(e.target.value)}>
          <option value="">All statuses</option>
          {['draft','scheduled','sending','completed','failed'].map(s => (
            <option key={s} value={s}>{s}</option>
          ))}
        </select>
      </form>

      <div className="card p-0 overflow-hidden">
        {loading ? (
          <div className="py-12 text-center text-gray-400">Loading…</div>
        ) : (
          <>
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Recipients</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Scheduled</th>
                  <th className="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {data?.data?.map(c => (
                  <tr key={c.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3">
                      <Link to={`/campaigns/${c.id}`} className="font-medium text-gray-900 hover:text-brand-600">{c.name}</Link>
                      {c.segment && <span className="ml-2 badge badge-blue">{c.segment.name}</span>}
                    </td>
                    <td className="px-4 py-3"><StatusBadge status={c.status} /></td>
                    <td className="px-4 py-3 text-gray-600">{c.total_recipients?.toLocaleString() ?? '—'}</td>
                    <td className="px-4 py-3 text-gray-600">
                      {c.scheduled_at ? new Date(c.scheduled_at).toLocaleDateString() : '—'}
                    </td>
                    <td className="px-4 py-3 text-gray-500">{new Date(c.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
                {data?.data?.length === 0 && (
                  <tr><td colSpan={5} className="py-12 text-center text-gray-400">No campaigns found</td></tr>
                )}
              </tbody>
            </table>
            <Pagination meta={data?.meta} onPageChange={setPage} />
          </>
        )}
      </div>
    </div>
  )
}
