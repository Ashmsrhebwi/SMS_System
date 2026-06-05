import React, { useEffect, useState } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function ContactDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()

  const [contact, setContact] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState(null)
  const [note, setNote]       = useState('')
  const [savingNote, setSavingNote] = useState(false)

  const load = () => {
    setLoading(true)
    api.get(`/contacts/${id}`)
      .then(r => setContact(r.data.data))
      .catch(setError)
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [id])

  const toggleOptIn = async () => {
    try {
      const res = await api.post(`/contacts/${id}/toggle-opt-in`)
      setContact(p => ({ ...p, opted_in: res.data.opted_in }))
    } catch (err) {
      setError(err)
    }
  }

  const handleDelete = async () => {
    if (!confirm('Delete this contact?')) return
    try {
      await api.delete(`/contacts/${id}`)
      navigate('/contacts')
    } catch (err) {
      setError(err)
    }
  }

  const saveNote = async (e) => {
    e.preventDefault()
    if (!note.trim()) return
    setSavingNote(true)
    try {
      await api.post(`/contacts/${id}/notes`, { note })
      setNote('')
      load()
    } catch (err) {
      setError(err)
    } finally {
      setSavingNote(false)
    }
  }

  if (loading) return <div className="flex items-center justify-center h-64 text-gray-400">Loading…</div>
  if (!contact) return <ErrorAlert error={error} />

  return (
    <div className="max-w-3xl space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{contact.name}</h1>
          <p className="text-sm text-gray-500">{contact.phone}</p>
        </div>
        <div className="flex gap-2">
          <button className="btn-secondary text-xs" onClick={toggleOptIn}>
            {contact.opted_in ? 'Opt Out' : 'Opt In'}
          </button>
          <Link to={`/contacts/${id}/edit`} className="btn-secondary text-xs">Edit</Link>
          <button className="btn-danger text-xs" onClick={handleDelete}>Delete</button>
        </div>
      </div>

      <ErrorAlert error={error} />

      <div className="card grid grid-cols-2 gap-4 text-sm">
        <div><p className="text-gray-500">Email</p><p>{contact.email ?? '—'}</p></div>
        <div><p className="text-gray-500">Status</p>
          <span className={contact.opted_in ? 'badge-green' : 'badge-red'}>
            {contact.opted_in ? 'Opted in' : 'Opted out'}
          </span>
        </div>
        <div>
          <p className="text-gray-500">Tags</p>
          <div className="flex flex-wrap gap-1 mt-1">
            {contact.tags?.map(t => <span key={t.id} className="badge badge-blue">{t.name}</span>)}
            {!contact.tags?.length && <span className="text-gray-400">None</span>}
          </div>
        </div>
        <div><p className="text-gray-500">Added</p><p>{new Date(contact.created_at).toLocaleDateString()}</p></div>
      </div>

      {/* Notes */}
      <div className="card space-y-4">
        <h2 className="font-semibold text-gray-900">Notes</h2>
        <form onSubmit={saveNote} className="flex gap-2">
          <input
            className="input flex-1"
            placeholder="Add a note…"
            value={note}
            onChange={e => setNote(e.target.value)}
          />
          <button type="submit" className="btn-primary text-xs" disabled={savingNote}>
            {savingNote ? 'Saving…' : 'Add'}
          </button>
        </form>
        <div className="space-y-3">
          {contact.contact_notes?.map(n => (
            <div key={n.id} className="rounded-lg bg-gray-50 p-3 text-sm">
              <p className="text-gray-700">{n.note}</p>
              <p className="text-xs text-gray-400 mt-1">{n.author?.name ?? 'Unknown'} · {new Date(n.created_at).toLocaleString()}</p>
            </div>
          ))}
          {!contact.contact_notes?.length && <p className="text-sm text-gray-400">No notes yet</p>}
        </div>
      </div>
    </div>
  )
}
