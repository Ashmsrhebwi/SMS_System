import React, { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import api from '../services/api'
import StatusBadge from '../components/StatusBadge'
import Pagination from '../components/Pagination'
import ErrorAlert from '../components/ErrorAlert'

export default function CampaignDetailPage() {
  const { id }     = useParams()
  const navigate   = useNavigate()

  const [data, setData]         = useState(null)
  const [messages, setMessages] = useState(null)
  const [msgPage, setMsgPage]   = useState(1)
  const [loading, setLoading]   = useState(true)
  const [error, setError]       = useState(null)
  const [actionLoading, setActionLoading] = useState('')

  const load = () => {
    setLoading(true)
    Promise.all([
      api.get(`/campaigns/${id}`),
      api.get(`/campaigns/${id}/messages`, { params: { page: msgPage } }),
    ]).then(([campRes, msgRes]) => {
      setData(campRes.data)
      setMessages(msgRes.data)
    }).catch(setError).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [id])
  useEffect(() => {
    api.get(`/campaigns/${id}/messages`, { params: { page: msgPage } })
      .then(r => setMessages(r.data))
  }, [msgPage])

  const action = async (endpoint, label) => {
    setActionLoading(label)
    setError(null)
    try {
      await api.post(`/campaigns/${id}/${endpoint}`)
      load()
    } catch (err) {
      setError(err)
    } finally {
      setActionLoading('')
    }
  }

  const handleDelete = async () => {
    if (!confirm('Delete this campaign?')) return
    try {
      await api.delete(`/campaigns/${id}`)
      navigate('/campaigns')
    } catch (err) {
      setError(err)
    }
  }

  if (loading) return <div className="flex items-center justify-center h-64 text-gray-400">Loading…</div>
  if (!data) return <ErrorAlert error={error} />

  const { campaign, stats } = data

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-gray-900">{campaign.name}</h1>
            <StatusBadge status={campaign.status} />
          </div>
          <p className="text-sm text-gray-500 mt-1">
            Created by {campaign.creator?.name ?? 'Unknown'} · {new Date(campaign.created_at).toLocaleString()}
          </p>
        </div>
        <div className="flex gap-2">
          {['draft', 'scheduled'].includes(campaign.status) && (
            <button className="btn-primary" disabled={!!actionLoading} onClick={() => action('send', 'send')}>
              {actionLoading === 'send' ? 'Sending…' : 'Send Now'}
            </button>
          )}
          <button className="btn-secondary" onClick={() => action('duplicate', 'dup')} disabled={!!actionLoading}>
            Duplicate
          </button>
          {campaign.status !== 'sending' && (
            <button className="btn-danger" onClick={handleDelete}>Delete</button>
          )}
        </div>
      </div>

      <ErrorAlert error={error} />

      {/* Message body */}
      <div className="card">
        <h2 className="font-semibold text-gray-900 mb-2">Message</h2>
        <p className="text-sm text-gray-700 whitespace-pre-wrap">{campaign.message_body}</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {[
          { label: 'Total',     value: stats.total },
          { label: 'Delivered', value: stats.delivered, rate: stats.delivery_rate },
          { label: 'Failed',    value: stats.failed,    rate: stats.failure_rate },
          { label: 'Clicked',   value: stats.clicked,   rate: stats.click_rate },
        ].map(({ label, value, rate }) => (
          <div key={label} className="card">
            <p className="text-sm text-gray-500">{label}</p>
            <p className="text-2xl font-bold text-gray-900">{value}</p>
            {rate !== undefined && <p className="text-xs text-gray-500">{rate}%</p>}
          </div>
        ))}
      </div>

      {/* Resend failed */}
      {stats.failed > 0 && (
        <button
          className="btn-secondary"
          onClick={() => action('resend-failed', 'resend')}
          disabled={!!actionLoading}
        >
          {actionLoading === 'resend' ? 'Re-queuing…' : `Re-queue ${stats.failed} Failed Messages`}
        </button>
      )}

      {/* Messages table */}
      <div className="card p-0 overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-200 font-medium text-sm text-gray-700">Messages</div>
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-200">
            <tr>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Contact</th>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Phone</th>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Status</th>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Cost</th>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Clicks</th>
              <th className="px-4 py-2 text-left font-medium text-gray-600">Updated</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {messages?.data?.map(m => (
              <tr key={m.id} className="hover:bg-gray-50">
                <td className="px-4 py-2">{m.contact?.name ?? 'Deleted'}</td>
                <td className="px-4 py-2 text-gray-500">{m.contact?.phone ?? '—'}</td>
                <td className="px-4 py-2"><StatusBadge status={m.status} /></td>
                <td className="px-4 py-2 text-gray-500">{m.cost ? `$${m.cost}` : '—'}</td>
                <td className="px-4 py-2 text-gray-500">{m.click?.click_count ?? 0}</td>
                <td className="px-4 py-2 text-gray-400">{new Date(m.updated_at).toLocaleString()}</td>
              </tr>
            ))}
            {messages?.data?.length === 0 && (
              <tr><td colSpan={6} className="py-8 text-center text-gray-400">No messages</td></tr>
            )}
          </tbody>
        </table>
        <Pagination meta={messages?.meta} onPageChange={setMsgPage} />
      </div>
    </div>
  )
}
