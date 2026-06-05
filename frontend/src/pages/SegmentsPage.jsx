import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function SegmentsPage() {
  const [segments, setSegments] = useState([])
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)

  const load = () => {
    setLoading(true)
    api.get('/segments').then(r => setSegments(r.data.data ?? [])).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const remove = async (s) => {
    if (!confirm(`Delete segment "${s.name}"?`)) return
    try {
      await api.delete(`/segments/${s.id}`)
      load()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Segments</h1>
      </div>
      <ErrorAlert error={error} />
      {loading ? (
        <div className="py-12 text-center text-gray-400">Loading…</div>
      ) : (
        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Eligible Contacts</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Campaigns</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {segments.map(s => (
                <tr key={s.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-900">{s.name}</td>
                  <td className="px-4 py-3 text-gray-600">{s.eligible_count?.toLocaleString() ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{s.campaigns_count ?? 0}</td>
                  <td className="px-4 py-3 text-right">
                    <button className="text-xs text-red-500 hover:text-red-700" onClick={() => remove(s)}>Delete</button>
                  </td>
                </tr>
              ))}
              {segments.length === 0 && <tr><td colSpan={4} className="py-12 text-center text-gray-400">No segments yet</td></tr>}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
